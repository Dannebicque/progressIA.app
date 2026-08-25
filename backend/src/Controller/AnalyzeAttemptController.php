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

        if (!$this->security->isGranted('ROLE_TEACHER')) {
            throw new AccessDeniedHttpException('Accès réservé aux enseignants.');
        }

        $student = $attempt->getUser();
        $institution = $student->getInstitution();

        if ($institution && !$institution->isAiEnabled()) {
            return new JsonResponse([
                'error' => 'L\'analyse par intelligence artificielle est désactivée pour cet établissement.'
            ], 403);
        }

        $apiKey = null;
        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
        $model = 'llama-3.3-70b-versatile';
        $provider = 'groq';

        if ($institution && $institution->getAiConfigType() === 'custom') {
            $apiKey = $institution->getAiApiKey();
            $provider = strtolower($institution->getAiProvider());
            $model = $institution->getAiModel() ?: 'llama-3.3-70b-versatile';

            if ($provider === 'openai') {
                $endpoint = 'https://api.openai.com/v1/chat/completions';
            } elseif ($provider === 'anthropic') {
                $endpoint = 'https://api.anthropic.com/v1/messages';
            } else {
                $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
            }
        } else {
            $apiKey = $_ENV['GROQ_API_KEY'] ?? $_SERVER['GROQ_API_KEY'] ?? null;
        }

        if (!$apiKey) {
            return new JsonResponse([
                'error' => 'La clé API de l\'intelligence artificielle n\'est pas configurée.'
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

        // Prepare the payload for Groq/OpenAI/Anthropic
        $systemMessage = "Tu es un assistant IA pédagogique qui aide l'enseignant à évaluer le rendu d'un étudiant. Tu devez obligatoirement renvoyer un objet JSON contenant exactement deux clés :\n" .
                         "- \"feedbackTeacher\": Une analyse critique et technique détaillée du rendu, destinée à l'enseignant (points forts, lacunes identifiées, conseils pour accompagner l'étudiant).\n" .
                         "- \"feedbackStudent\": Un retour d'évaluation constructif, encourageant, positif et bienveillant, rédigé en français et destiné directement à l'étudiant (adresse-toi à lui en utilisant son prénom, sois clair et pédagogue, ne mentionne pas qu'il s'agit d'une analyse automatisée).\n\n" .
                         "Les valeurs de ces deux clés doivent obligatoirement être de simples chaînes de caractères (string) et non pas des tableaux ou des objets.";

        $userMessage = json_encode($promptData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $headers = ['Content-Type: application/json'];
        if ($provider === 'anthropic') {
            $headers[] = 'x-api-key: ' . $apiKey;
            $headers[] = 'anthropic-version: 2023-06-01';
            $payload = [
                'model' => $model,
                'max_tokens' => 2000,
                'system' => $systemMessage,
                'messages' => [
                    ['role' => 'user', 'content' => $userMessage]
                ]
            ];
        } else {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
            $payload = [
                'model' => $model,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.5,
            ];
        }

        // Call AI API via cURL
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'appel à l\'API IA : ' . $curlError
            ], 500);
        }

        if ($httpCode !== 200) {
            return new JsonResponse([
                'error' => 'L\'API IA a renvoyé un code HTTP ' . $httpCode,
                'details' => json_decode($response, true) ?: $response
            ], 502);
        }

        $result = json_decode($response, true);
        
        if ($provider === 'anthropic') {
            $content = $result['content'][0]['text'] ?? '';
            $promptTokens = $result['usage']['input_tokens'] ?? 0;
            $completionTokens = $result['usage']['output_tokens'] ?? 0;
        } else {
            $content = $result['choices'][0]['message']['content'] ?? '';
            $promptTokens = $result['usage']['prompt_tokens'] ?? 0;
            $completionTokens = $result['usage']['completion_tokens'] ?? 0;
        }

        // Log usage if student has an institution
        if ($institution) {
            // Groq/Llama input: 0.15$ / 1M, output: 0.60$ / 1M
            // Convert to € (1 USD = 0.92 EUR)
            $inputCost = ($promptTokens / 1000000) * 0.15 * 0.92;
            $outputCost = ($completionTokens / 1000000) * 0.60 * 0.92;
            $totalCost = $inputCost + $outputCost;

            $log = (new \App\Entity\AiUsageLog())
                ->setInstitution($institution)
                ->setUser($currentUser)
                ->setFeature('attempt_analysis')
                ->setPromptTokens($promptTokens)
                ->setCompletionTokens($completionTokens)
                ->setEstimatedCost(sprintf('%.5f', $totalCost));

            $this->em->persist($log);
        }

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
