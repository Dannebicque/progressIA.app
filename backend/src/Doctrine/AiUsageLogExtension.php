<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\AiUsageLog;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class AiUsageLogExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->addFilter($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->addFilter($queryBuilder, $resourceClass);
    }

    private function addFilter(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (AiUsageLog::class !== $resourceClass) {
            return;
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        // Use isGranted checks to respect hierarchy
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return; // Super Admin sees all logs
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        if ($this->security->isGranted('ROLE_SCHOOL_ADMIN')) {
            $institution = $currentUser->getInstitution();
            if (!$institution) {
                $queryBuilder->andWhere('1 = 0');
                return;
            }
            $queryBuilder->andWhere(sprintf('%s.institution = :inst', $rootAlias))
                         ->setParameter('inst', $institution);
            return;
        }

        // Teachers or students don't have access to global logs
        $queryBuilder->andWhere('1 = 0');
    }
}
