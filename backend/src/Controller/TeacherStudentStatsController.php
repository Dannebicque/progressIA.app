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

        if (!$this->security->isGranted('ROLE_TEACHER')) {
            throw new AccessDeniedHttpException('Accès réservé aux enseignants.');
        }

        // Fetch users using query builder to filter by institution/role
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('u');
        
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            // Super Admin sees all users
        } elseif ($this->security->isGranted('ROLE_SCHOOL_ADMIN')) {
            $institution = $currentUser->getInstitution();
            if (!$institution) {
                return new JsonResponse([]);
            }
            $qb->andWhere('u.institution = :inst')
               ->setParameter('inst', $institution);
        } elseif ($this->security->isGranted('ROLE_TEACHER')) {
            $institutions = $currentUser->getInstitutions();
            if ($institutions->isEmpty()) {
                return new JsonResponse([]);
            }
            $qb->andWhere('u.institution IN (:insts)')
               ->setParameter('insts', $institutions);
        }

        $allUsers = $qb->getQuery()->getResult();
        $students = [];
        foreach ($allUsers as $u) {
            // Add users that are not teachers/admins/superadmins
            if (!in_array('ROLE_TEACHER', $u->getRoles(), true) &&
                !in_array('ROLE_SCHOOL_ADMIN', $u->getRoles(), true) &&
                !in_array('ROLE_SUPER_ADMIN', $u->getRoles(), true)) {
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
                        $questionsData = [];
                        foreach ($e->getQuestions() as $q) {
                            $questionsData[] = [
                                'id' => $q->getId(),
                                'statement' => $q->getStatement(),
                                'type' => $q->getType(),
                                'fileRequired' => $q->isFileRequired(),
                            ];
                        }
                        $evaluations[] = [
                            'id' => $e->getId(),
                            'title' => $e->getTitle(),
                            'pointsReward' => $e->getPointsReward(),
                            'questions' => $questionsData,
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
                    'attemptId' => $ea->getId(),
                    'score' => $score,
                    'maxScore' => $maxScore,
                    'passed' => $passed,
                    'answers' => $ea->getAnswers(),
                    'feedbackTeacher' => $ea->getFeedbackTeacher(),
                    'feedbackStudent' => $ea->getFeedbackStudent(),
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
                        'attemptId' => $hasAttempt ? $attemptMap[$uid][$eid]['attemptId'] : null,
                        'title' => $eMeta['title'],
                        'pointsReward' => $eMeta['pointsReward'],
                        'attempted' => $hasAttempt,
                        'score' => $score,
                        'maxScore' => $maxScore,
                        'passed' => $passed,
                        'questions' => $eMeta['questions'] ?? [],
                        'answers' => $hasAttempt ? $attemptMap[$uid][$eid]['answers'] : [],
                        'feedbackTeacher' => $hasAttempt ? $attemptMap[$uid][$eid]['feedbackTeacher'] : null,
                        'feedbackStudent' => $hasAttempt ? $attemptMap[$uid][$eid]['feedbackStudent'] : null,
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
                'studentYear' => $s->getStudentFormation() ? $s->getStudentFormation()->getName() : ($s->getStudentYear() ?? ''),
                'studentInstitution' => $s->getInstitution() ? $s->getInstitution()->getName() : ($s->getStudentInstitution() ?? ''),
                'studentSemester' => $s->getStudentSemester() ? $s->getStudentSemester()->getName() : '',
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
