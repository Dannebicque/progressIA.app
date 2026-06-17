<?php

namespace App\EventListener;

use App\Entity\Chapter;
use App\Entity\Choice;
use App\Entity\Course;
use App\Entity\Evaluation;
use App\Entity\Page;
use App\Entity\Question;
use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEventListener;
use Doctrine\ORM\Event\OnFlushEventArgs;

#[AsEventListener(event: 'onFlush')]
class CourseUpdateListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $coursesToUpdate = [];

        $findCourse = function ($entity) {
            if ($entity instanceof Course) {
                return $entity;
            }
            if ($entity instanceof Session) {
                return $entity->getCourse();
            }
            if ($entity instanceof Chapter) {
                return $entity->getSession()?->getCourse();
            }
            if ($entity instanceof Page) {
                return $entity->getChapter()?->getSession()?->getCourse();
            }
            if ($entity instanceof Evaluation) {
                return $entity->getChapter()?->getSession()?->getCourse();
            }
            if ($entity instanceof Question) {
                return $entity->getEvaluation()?->getChapter()?->getSession()?->getCourse();
            }
            if ($entity instanceof Choice) {
                return $entity->getQuestion()?->getEvaluation()?->getChapter()?->getSession()?->getCourse();
            }
            return null;
        };

        // Gather all courses affected by insertions, updates or deletions
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($course = $findCourse($entity)) {
                $coursesToUpdate[spl_object_hash($course)] = $course;
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($course = $findCourse($entity)) {
                $coursesToUpdate[spl_object_hash($course)] = $course;
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($course = $findCourse($entity)) {
                $coursesToUpdate[spl_object_hash($course)] = $course;
            }
        }

        foreach ($coursesToUpdate as $course) {
            // Only update existing courses that have an ID
            if ($course->getId() !== null) {
                $course->setUpdatedAt(new \DateTimeImmutable());
                $uow->recomputeSingleEntityChangeSet(
                    $em->getClassMetadata(Course::class),
                    $course
                );
            }
        }
    }
}
