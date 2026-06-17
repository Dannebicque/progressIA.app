<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\EvaluationAttempt;
use App\Entity\PageCompletion;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final class RecentCoursesController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/me/recent-courses', name: 'api_me_recent_courses', methods: ['GET'])]
    public function __invoke(): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentification requise.');
        }

        $courses = [];

        if (in_array('ROLE_TEACHER', $user->getRoles(), true)) {
            // For teachers: last 4 edited courses
            $courses = $this->em->getRepository(Course::class)->findBy(
                [],
                ['updatedAt' => 'DESC', 'id' => 'DESC'],
                4
            );
        } else {
            // For students: last 4 followed courses (pages completed or evaluation attempts)
            // Query most recent page completions
            $completions = $this->em->createQueryBuilder()
                ->select('c.id AS courseId, MAX(pc.createdAt) AS maxDate')
                ->from(PageCompletion::class, 'pc')
                ->join('pc.page', 'p')
                ->join('p.chapter', 'ch')
                ->join('ch.session', 's')
                ->join('s.course', 'c')
                ->where('pc.user = :user')
                ->andWhere('c.visible = true')
                ->groupBy('c.id')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();

            // Query most recent evaluation attempts
            $attempts = $this->em->createQueryBuilder()
                ->select('c.id AS courseId, MAX(ea.createdAt) AS maxDate')
                ->from(EvaluationAttempt::class, 'ea')
                ->join('ea.evaluation', 'e')
                ->join('e.chapter', 'ch')
                ->join('ch.session', 's')
                ->join('s.course', 'c')
                ->where('ea.user = :user')
                ->andWhere('c.visible = true')
                ->groupBy('c.id')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();

            $courseDates = [];
            foreach ($completions as $row) {
                $courseId = (int) $row['courseId'];
                $date = $row['maxDate'] instanceof \DateTimeInterface ? $row['maxDate'] : new \DateTimeImmutable($row['maxDate']);
                $courseDates[$courseId] = $date;
            }

            foreach ($attempts as $row) {
                $courseId = (int) $row['courseId'];
                $date = $row['maxDate'] instanceof \DateTimeInterface ? $row['maxDate'] : new \DateTimeImmutable($row['maxDate']);
                if (!isset($courseDates[$courseId]) || $date > $courseDates[$courseId]) {
                    $courseDates[$courseId] = $date;
                }
            }

            // Sort courses by activity date descending
            arsort($courseDates);

            // Take top 4
            $recentCourseIds = array_slice(array_keys($courseDates), 0, 4);

            if (!empty($recentCourseIds)) {
                $unorderedCourses = $this->em->getRepository(Course::class)->findBy(['id' => $recentCourseIds]);
                $coursesMap = [];
                foreach ($unorderedCourses as $c) {
                    $coursesMap[$c->getId()] = $c;
                }
                foreach ($recentCourseIds as $id) {
                    if (isset($coursesMap[$id])) {
                        $courses[] = $coursesMap[$id];
                    }
                }
            }
        }

        $json = $this->serializer->serialize($courses, 'json', ['groups' => ['course:read']]);

        return new Response($json, Response::HTTP_OK, [
            'Content-Type' => 'application/json',
        ]);
    }
}
