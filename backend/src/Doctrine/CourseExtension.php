<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Course;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class CourseExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
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
        if (Course::class !== $resourceClass) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            // Unauthenticated users can only see public/visible courses
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder->andWhere(sprintf('%s.visible = true', $rootAlias));
            return;
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return; // Super Admin sees everything
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        if (in_array('ROLE_SCHOOL_ADMIN', $user->getRoles(), true)) {
            // School admin sees courses of their institution
            $institution = $user->getInstitution();
            if (!$institution) {
                $queryBuilder->andWhere('1 = 0');
                return;
            }
            $queryBuilder->innerJoin(sprintf('%s.institutions', $rootAlias), 'inst')
                ->andWhere('inst.id = :institutionId')
                ->setParameter('institutionId', $institution->getId());
            return;
        }

        if (in_array('ROLE_TEACHER', $user->getRoles(), true)) {
            // Teacher sees courses where they are registered as a teacher
            $queryBuilder->innerJoin(sprintf('%s.teachers', $rootAlias), 't')
                ->andWhere('t.id = :teacherId')
                ->setParameter('teacherId', $user->getId());
            return;
        }

        if (in_array('ROLE_STUDENT', $user->getRoles(), true)) {
            // Student sees courses of their institution and registered semester
            $institution = $user->getInstitution();
            $semester = $user->getStudentSemester();
            if (!$institution || !$semester) {
                $queryBuilder->andWhere('1 = 0');
                return;
            }

            $queryBuilder->innerJoin(sprintf('%s.institutions', $rootAlias), 'inst')
                ->innerJoin(sprintf('%s.semesters', $rootAlias), 'sem')
                ->andWhere('inst.id = :institutionId')
                ->andWhere('sem.id = :semesterId')
                ->andWhere(sprintf('%s.visible = true', $rootAlias))
                ->setParameter('institutionId', $institution->getId())
                ->setParameter('semesterId', $semester->getId());
            return;
        }
    }
}
