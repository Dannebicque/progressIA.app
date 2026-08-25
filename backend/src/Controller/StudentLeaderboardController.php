<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class StudentLeaderboardController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
    ) {
    }

    #[Route('/api/me/leaderboard', name: 'api_me_leaderboard', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentification requise.');
        }

        $institution = $currentUser->getInstitution();

        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
           ->from(User::class, 'u')
           ->orderBy('u.points', 'DESC');

        if ($institution) {
            $qb->where('u.institution = :institution')
               ->setParameter('institution', $institution);
        }

        /** @var User[] $allUsers */
        $allUsers = $qb->getQuery()->getResult();

        $fullList = [];
        $rank = 1;

        foreach ($allUsers as $u) {
            $roles = $u->getRoles();
            // Filter out non-student roles
            if (
                in_array('ROLE_TEACHER', $roles, true) ||
                in_array('ROLE_SCHOOL_ADMIN', $roles, true) ||
                in_array('ROLE_SUPER_ADMIN', $roles, true)
            ) {
                continue;
            }

            $fullList[] = [
                'rank' => $rank++,
                'name' => $u->getName(),
                'points' => $u->getPoints(),
                'isCurrentUser' => ($u->getId() === $currentUser->getId()),
            ];
        }

        // Return top 10
        $topTen = array_slice($fullList, 0, 10);

        // Check if current user is inside the top 10
        $isInTopTen = false;
        foreach ($topTen as $item) {
            if ($item['isCurrentUser']) {
                $isInTopTen = true;
                break;
            }
        }

        // If not, append current user's ranking details to the end
        if (!$isInTopTen) {
            foreach ($fullList as $item) {
                if ($item['isCurrentUser']) {
                    $topTen[] = $item;
                    break;
                }
            }
        }

        return new JsonResponse($topTen);
    }
}
