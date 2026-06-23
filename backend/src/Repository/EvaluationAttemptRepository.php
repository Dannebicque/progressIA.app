<?php

namespace App\Repository;

use App\Entity\Evaluation;
use App\Entity\EvaluationAttempt;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvaluationAttempt>
 */
class EvaluationAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvaluationAttempt::class);
    }

    public function bestScore(User $user, Evaluation $evaluation): int
    {
        $best = $this->createQueryBuilder('a')
            ->select('MAX(a.score) AS best')
            ->where('a.user = :user')->andWhere('a.evaluation = :eval')
            ->setParameter('user', $user)->setParameter('eval', $evaluation)
            ->getQuery()->getSingleScalarResult();

        return (int) ($best ?? 0);
    }

    /** @return list<EvaluationAttempt> */
    public function forUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :user')->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()->getResult();
    }
}
