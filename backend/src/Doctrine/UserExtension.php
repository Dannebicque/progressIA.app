<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class UserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
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
        if (User::class !== $resourceClass) {
            return;
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        if (in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            return; // Super Admin sees all users
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        if (in_array('ROLE_SCHOOL_ADMIN', $currentUser->getRoles(), true)) {
            // School admin sees users of their own institution
            $institution = $currentUser->getInstitution();
            if (!$institution) {
                $queryBuilder->andWhere('1 = 0');
                return;
            }
            $queryBuilder->andWhere(sprintf('%s.institution = :instId', $rootAlias))
                ->setParameter('instId', $institution->getId());
            return;
        }

        if (in_array('ROLE_TEACHER', $currentUser->getRoles(), true)) {
            // Teacher sees users in the institutions they teach at
            $institutions = $currentUser->getInstitutions();
            if ($institutions->isEmpty()) {
                $queryBuilder->andWhere('1 = 0');
                return;
            }

            $instIds = array_map(fn($inst) => $inst->getId(), $institutions->toArray());
            $queryBuilder->andWhere(sprintf('%s.institution IN (:instIds)', $rootAlias))
                ->setParameter('instIds', $instIds);
            return;
        }

        // Students can only see themselves
        $queryBuilder->andWhere(sprintf('%s.id = :currentUserId', $rootAlias))
            ->setParameter('currentUserId', $currentUser->getId());
    }
}
