<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EvaluationAttemptRepository;
use App\Repository\PageCompletionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Returns the current user's progress: completed page ids + best score per evaluation.
 */
#[AsController]
final class ProgressController
{
    public function __construct(
        private readonly PageCompletionRepository $completions,
        private readonly EvaluationAttemptRepository $attempts,
        private readonly Security $security,
    ) {
    }

    #[Route('/api/me/progress', name: 'api_me_progress', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentification requise.');
        }

        // best attempt per evaluation
        $best = [];
        foreach ($this->attempts->forUser($user) as $a) {
            $eid = $a->getEvaluation()?->getId();
            if (null === $eid) {
                continue;
            }
            if (!isset($best[$eid]) || $a->getScore() > $best[$eid]['score']) {
                $best[$eid] = [
                    'evaluation' => $eid,
                    'score' => $a->getScore(),
                    'maxScore' => $a->getMaxScore(),
                    'passed' => $a->isPassed(),
                ];
            }
        }

        return new JsonResponse([
            'completedPageIds' => $this->completions->completedPageIds($user),
            'evaluations' => array_values($best),
        ]);
    }
}
