<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\PageCompletion;
use App\Entity\User;
use App\Repository\PageCompletionRepository;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Marks a content page as completed for the current user and awards its points
 * (only the first time). Returns the updated total and any newly earned badges.
 */
#[AsController]
final class CompletePageController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PageCompletionRepository $completions,
        private readonly GamificationService $gamification,
        private readonly Security $security,
    ) {
    }

    #[Route('/api/pages/{id}/complete', name: 'api_page_complete', methods: ['POST'])]
    public function __invoke(Page $page): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentification requise.');
        }

        $already = $this->completions->findOneByUserAndPage($user, $page);
        if ($already) {
            return new JsonResponse([
                'alreadyDone' => true,
                'pointsEarned' => 0,
                'totalPoints' => $user->getPoints(),
                'newBadges' => [],
            ]);
        }

        $completion = (new PageCompletion())->setUser($user)->setPage($page);
        $this->em->persist($completion);
        $this->gamification->awardPoints($user, $page->getPoints());
        // flush first so recheckBadges() sees this completion when querying the DB
        $this->em->flush();

        $newBadges = $this->gamification->recheckBadges($user);
        $this->em->flush();

        return new JsonResponse([
            'alreadyDone' => false,
            'pointsEarned' => $page->getPoints(),
            'totalPoints' => $user->getPoints(),
            'newBadges' => array_map([$this, 'badge'], $newBadges),
        ]);
    }

    private function badge(\App\Entity\Badge $b): array
    {
        return ['code' => $b->getCode(), 'icon' => $b->getIcon(), 'label' => $b->getLabel(), 'description' => $b->getDescription()];
    }
}
