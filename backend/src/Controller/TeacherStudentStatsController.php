<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\EvaluationAttempt;
use App\Entity\PageCompletion;
use App\Entity\User;
use App\Entity\Badge;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class TeacherStudentStatsController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
    ) {
    }

    #[Route('/api/teacher/students', name: 'api_teacher_students', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentification requise.');
        }

        if (!in_array('ROLE_TEACHER', $currentUser->getRoles(), true)) {
            throw new AccessDeniedHttpException('Accès réservé aux enseignants.');
        }

        // Fetch all users
        $allUsers = $this->em->getRepository(User::class)->findAll();
        $students = [];
        foreach ($allUsers as $u) {
            if (!in_array('ROLE_TEACHER', $u->getRoles(), true)) {
                $students[] = $u;
            }
        }

        // Fetch all courses
        $courses = $this->em->getRepository(Course::class)->findAll();

        // Build course metadata map
        $courseMap = [];
        foreach ($courses as $c) {
            $pageIds = [];
            $evaluations = [];
            foreach ($c->getSessions() as $s) {
                foreach ($s->getChapters() as $ch) {
                    foreach ($ch->getPages() as $p) {
                        $pageIds[] = $p->getId();
                    }
                    foreach ($ch->getEvaluations() as $e) {
                        $evaluations[] = [
                            'id' => $e->getId(),
                            'title' => $e->getTitle(),
                            'pointsReward' => $e->getPointsReward(),
                        ];
                    }
                }
            }
            $courseMap[$c->getId()] = [
                'id' => $c->getId(),
                'title' => $c->getTitle(),
                'accentColor' => $c->getAccentColor() ?? '#7c3aed',
                'pageIds' => $pageIds,
                'evaluations' => $evaluations,
            ];
        }

        // Fetch all PageCompletions
        $completions = $this->em->getRepository(PageCompletion::class)->findAll();
        $completionMap = []; // userId => [pageId => true]
        foreach ($completions as $pc) {
            $uid = $pc->getUser()->getId();
            $pid = $pc->getPage()->getId();
            $completionMap[$uid][$pid] = true;
        }

        // Fetch all EvaluationAttempts
        $attempts = $this->em->getRepository(EvaluationAttempt::class)->findAll();
        $attemptMap = []; // userId => [evalId => ['score' => int, 'maxScore' => int, 'passed' => bool]]
        foreach ($attempts as $ea) {
            $uid = $ea->getUser()->getId();
            $eid = $ea->getEvaluation()->getId();
            $score = $ea->getScore();
            $maxScore = $ea->getMaxScore();
            $passed = $ea->isPassed();

            if (!isset($attemptMap[$uid][$eid]) || $score > $attemptMap[$uid][$eid]['score']) {
                $attemptMap[$uid][$eid] = [
                    'score' => $score,
                    'maxScore' => $maxScore,
                    'passed' => $passed,
                ];
            }
        }

        // Build result
        $result = [];
        foreach ($students as $s) {
            $uid = $s->getId();
            $badges = [];
            foreach ($s->getBadges() as $b) {
                $badges[] = [
                    'code' => $b->getCode(),
                    'icon' => $b->getIcon(),
                    'label' => $b->getLabel(),
                    'description' => $b->getDescription(),
                ];
            }

            $courseStats = [];
            foreach ($courseMap as $cid => $cMeta) {
                $totalPages = count($cMeta['pageIds']);
                $completedCount = 0;
                foreach ($cMeta['pageIds'] as $pid) {
                    if (isset($completionMap[$uid][$pid])) {
                        $completedCount++;
                    }
                }

                $evalStats = [];
                $passedCount = 0;
                $totalScore = 0;
                $totalMaxScore = 0;
                foreach ($cMeta['evaluations'] as $eMeta) {
                    $eid = $eMeta['id'];
                    $hasAttempt = isset($attemptMap[$uid][$eid]);
                    $score = $hasAttempt ? $attemptMap[$uid][$eid]['score'] : null;
                    $maxScore = $hasAttempt ? $attemptMap[$uid][$eid]['maxScore'] : null;
                    $passed = $hasAttempt ? $attemptMap[$uid][$eid]['passed'] : false;

                    if ($passed) {
                        $passedCount++;
                    }
                    if ($hasAttempt) {
                        $totalScore += $score;
                        $totalMaxScore += $maxScore;
                    }

                    $evalStats[] = [
                        'id' => $eid,
                        'title' => $eMeta['title'],
                        'pointsReward' => $eMeta['pointsReward'],
                        'attempted' => $hasAttempt,
                        'score' => $score,
                        'maxScore' => $maxScore,
                        'passed' => $passed,
                    ];
                }

                $courseStats[] = [
                    'courseId' => $cid,
                    'courseTitle' => $cMeta['title'],
                    'courseAccentColor' => $cMeta['accentColor'],
                    'totalPages' => $totalPages,
                    'completedPages' => $completedCount,
                    'progressPct' => $totalPages > 0 ? (int) round(($completedCount / $totalPages) * 100) : 0,
                    'evaluations' => $evalStats,
                    'passedEvaluationsCount' => $passedCount,
                    'totalEvaluationsCount' => count($cMeta['evaluations']),
                    'totalEvaluationScore' => $totalScore,
                    'totalEvaluationMaxScore' => $totalMaxScore,
                ];
            }

            $result[] = [
                'id' => $uid,
                'name' => $s->getName(),
                'email' => $s->getEmail(),
                'studentGroup' => $s->getStudentGroup() ?? '',
                'studentYear' => $s->getStudentYear() ?? '',
                'studentInstitution' => $s->getStudentInstitution() ?? '',
                'points' => $s->getPoints(),
                'badges' => $badges,
                'courseStats' => $courseStats,
            ];
        }

        // Sort students by name
        usort($result, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return new JsonResponse($result);
    }
}
