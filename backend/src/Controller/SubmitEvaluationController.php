<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Entity\EvaluationAttempt;
use App\Entity\Question;
use App\Entity\User;
use App\Repository\EvaluationAttemptRepository;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Grades an evaluation submission server-side (the correct answers never leave
 * the server), records the attempt and awards points + badges.
 *
 * QCM questions: full points only if the selected choices match the correct set
 * exactly. Free questions: graded as participation (points granted for a
 * non-empty answer; they would need manual review in a real setting).
 */
#[AsController]
final class SubmitEvaluationController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EvaluationAttemptRepository $attempts,
        private readonly GamificationService $gamification,
        private readonly Security $security,
    ) {
    }

    #[Route('/api/evaluations/{id}/submit', name: 'api_evaluation_submit', methods: ['POST'])]
    public function __invoke(Evaluation $evaluation, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentification requise.');
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $answers = $payload['answers'] ?? [];

        // index submitted answers by question id
        $byQuestion = [];
        foreach ($answers as $a) {
            if (isset($a['question'])) {
                $byQuestion[(int) $a['question']] = $a;
            }
        }

        $score = 0;
        $maxScore = 0;
        $results = [];
        foreach ($evaluation->getQuestions() as $question) {
            $maxScore += $question->getPoints();
            $submitted = $byQuestion[$question->getId()] ?? [];
            [$correct, $awarded] = $this->grade($question, $submitted);
            $score += $awarded;
            $results[] = [
                'question' => $question->getId(),
                'correct' => $correct,
                'awarded' => $awarded,
                'maxPoints' => $question->getPoints(),
            ];
        }

        $passed = $maxScore > 0 && ($score * 2) >= $maxScore;

        // anti-farming: only reward improvement over the best previous score,
        // plus a one-time bonus the first time the evaluation is passed.
        $previousBest = $this->attempts->bestScore($user, $evaluation);
        $previouslyPassed = false;
        foreach ($this->attempts->forUser($user) as $prev) {
            if ($prev->getEvaluation() === $evaluation && $prev->isPassed()) {
                $previouslyPassed = true;
                break;
            }
        }

        $pointsEarned = max(0, $score - $previousBest);
        if ($passed && !$previouslyPassed) {
            $pointsEarned += $evaluation->getPointsReward();
        }

        $attempt = (new EvaluationAttempt())
            ->setUser($user)
            ->setEvaluation($evaluation)
            ->setScore($score)
            ->setMaxScore($maxScore)
            ->setPassed($passed)
            ->setAnswers(is_array($answers) ? $answers : []);
        $this->em->persist($attempt);
        $this->gamification->awardPoints($user, $pointsEarned);
        // flush first so recheckBadges() sees this attempt when querying the DB
        $this->em->flush();

        $newBadges = $this->gamification->recheckBadges($user);
        $this->em->flush();

        return new JsonResponse([
            'score' => $score,
            'maxScore' => $maxScore,
            'passed' => $passed,
            'pointsEarned' => $pointsEarned,
            'totalPoints' => $user->getPoints(),
            'results' => $results,
            'newBadges' => array_map([$this, 'badge'], $newBadges),
        ]);
    }

    /**
     * @param array<string, mixed> $submitted
     *
     * @return array{0: bool, 1: int} [correct, awardedPoints]
     */
    private function grade(Question $question, array $submitted): array
    {
        if (Question::TYPE_FREE === $question->getType()) {
            $text = trim((string) ($submitted['text'] ?? ''));
            $ok = '' !== $text;

            return [$ok, $ok ? $question->getPoints() : 0];
        }

        // QCM: exact match of the correct choice set
        $correctIds = $question->correctChoiceIds();
        sort($correctIds);
        $picked = array_values(array_unique(array_map('intval', (array) ($submitted['choices'] ?? []))));
        sort($picked);

        $ok = $picked === $correctIds && [] !== $correctIds;

        return [$ok, $ok ? $question->getPoints() : 0];
    }

    private function badge(\App\Entity\Badge $b): array
    {
        return ['code' => $b->getCode(), 'icon' => $b->getIcon(), 'label' => $b->getLabel(), 'description' => $b->getDescription()];
    }
}
