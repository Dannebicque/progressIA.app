<?php

namespace App\Repository;

use App\Entity\Page;
use App\Entity\PageCompletion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PageCompletion>
 */
class PageCompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageCompletion::class);
    }

    public function findOneByUserAndPage(User $user, Page $page): ?PageCompletion
    {
        return $this->findOneBy(['user' => $user, 'page' => $page]);
    }

    /** @return list<int> ids of pages completed by the user */
    public function completedPageIds(User $user): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.page) AS pageId')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn ($r) => (int) $r['pageId'], $rows);
    }
}
