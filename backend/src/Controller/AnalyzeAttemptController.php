<?php

namespace App\Controller;

use App\Entity\EvaluationAttempt;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class AnalyzeAttemptController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
    ) {
    }

    #[Route('/api/attempts/{id}/analyze-ai', name: 'api_attempt_analyze_ai', methods: ['POST'])]
    public function __invoke(EvaluationAttempt $attempt): JsonResponse
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentification requise.');
        }

        if (!in_array('ROLE_TEACHER', $currentUser->getRoles(), true)) {
            throw new AccessDeniedHttpException('Accès réservé aux enseignants.');
        }

        $groqApiKey = $_ENV['GROQ_API_KEY'] ?? $_SERVER['GROQ_API_KEY'] ?? null;
        if (!$groqApiKey) {
            return new JsonResponse([
                'error' => 'La clé API Groq n\'est pas configurée. Veuillez définir la variable GROQ_API_KEY dans votre fichier .env.local backend.'
            ], 400);
        }

        // Build prompt data with student details and answers
        $promptData = [
            'student' => $attempt->getUser()->getName(),
            'quiz' => $attempt->getEvaluation()->getTitle(),
            'score' => $attempt->getScore() . ' / ' . $attempt->getMaxScore(),
            'answers' => []
        ];

        foreach ($attempt->getEvaluation()->getQuestions() as $q) {
            $studentAnswer = null;
            foreach ($attempt->getAnswers() as $ans) {
                if (isset($ans['question']) && (int)$ans['question'] === $q->getId()) {
                    $studentAnswer = $ans;
                    break;
                }
            }

            $qData = [
                'type' => $q->getType(),
                'statement' => $q->getStatement(),
                'points' => $q->getPoints(),
            ];

            if ($q->getType() === 'qcm') {
                $choices = [];
                $selected = [];
                foreach ($q->getChoices() as $c) {
                    $choices[] = $c->getText() . ($c->isCorrect() ? ' (Correct)' : '');
                    if ($studentAnswer && isset($studentAnswer['choices']) && in_array($c->getId(), $studentAnswer['choices'])) {
                        $selected[] = $c->getText();
                    }
                }
                $qData['choices'] = $choices;
                $qData['student_selected'] = $selected;
            } elseif ($q->getType() === 'file') {
                $qData['student_response'] = $studentAnswer ? 'Fichier : ' . ($studentAnswer['file'] ?? '') . ' (' . ($studentAnswer['text'] ?? '') . ')' : '';
            } else {
                $qData['student_response'] = $studentAnswer ? ($studentAnswer['text'] ?? '') : '';
            }

            $promptData['answers'][] = $qData;
        }

        // Prepare the payload for Groq
        $systemMessage = "Tu es un assistant IA pédagogique qui aide l'enseignant à évaluer le rendu d'un étudiant. Tu dois obligatoirement renvoyer un objet JSON contenant exactement deux clés :\n" .
                         "- \"feedbackTeacher\": Une analyse critique et technique détaillée du rendu, destinée à l'enseignant (points forts, lacunes identifiées, conseils pour accompagner l'étudiant).\n" .
                         "- \"feedbackStudent\": Un retour d'évaluation constructif, encourageant, positif et bienveillant, rédigé en français et destiné directement à l'étudiant (adresse-toi à lui en utilisant son prénom, sois clair et pédagogue, ne mentionne pas qu'il s'agit d'une analyse automatisée).\n\n" .
                         "Les valeurs de ces deux clés doivent obligatoirement être de simples chaînes de caractères (string) et non pas des tableaux ou des objets.";

        $userMessage = json_encode($promptData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $payload = [
            'model' => 'llama-3.3-70b-versatile',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => 0.5,
        ];

        // Call Groq API via cURL
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $groqApiKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'appel à l\'API Groq : ' . $curlError
            ], 500);
        }

        if ($httpCode !== 200) {
            return new JsonResponse([
                'error' => 'Groq API a renvoyé un code HTTP ' . $httpCode,
                'details' => json_decode($response, true) ?: $response
            ], 502);
        }

        $result = json_decode($response, true);
        $content = $result['choices'][0]['message']['content'] ?? '';
        $feedbacks = json_decode($content, true);

        if (!isset($feedbacks['feedbackTeacher']) || !isset($feedbacks['feedbackStudent'])) {
            return new JsonResponse([
                'error' => 'Le format de réponse de l\'IA est invalide.',
                'raw' => $content
            ], 502);
        }

        $feedbackTeacher = is_array($feedbacks['feedbackTeacher'])
            ? json_encode($feedbacks['feedbackTeacher'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            : (string) $feedbacks['feedbackTeacher'];

        $feedbackStudent = is_array($feedbacks['feedbackStudent'])
            ? json_encode($feedbacks['feedbackStudent'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            : (string) $feedbacks['feedbackStudent'];

        // Save feedbacks in evaluation attempt
        $attempt->setFeedbackTeacher($feedbackTeacher);
        $attempt->setFeedbackStudent($feedbackStudent);

        $this->em->flush();

        return new JsonResponse([
            'id' => $attempt->getId(),
            'feedbackTeacher' => $attempt->getFeedbackTeacher(),
            'feedbackStudent' => $attempt->getFeedbackStudent(),
        ]);
    }
}
