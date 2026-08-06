<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Session;
use App\Entity\User;
use App\Entity\SentEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class TeacherEmailController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly MailerInterface $mailer,
    ) {
    }

    private function checkTeacherAccess(): User
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentification requise.');
        }

        if (!in_array('ROLE_TEACHER', $currentUser->getRoles(), true)) {
            throw new AccessDeniedHttpException('Accès réservé aux enseignants.');
        }

        return $currentUser;
    }

    #[Route('/api/teacher/courses/{id}/groups', name: 'api_teacher_course_groups', methods: ['GET'])]
    public function getCourseGroups(int $id): JsonResponse
    {
        $this->checkTeacherAccess();

        $course = $this->em->getRepository(Course::class)->find($id);
        if (!$course) {
            throw new NotFoundHttpException('Cours introuvable.');
        }

        // Fetch all users who are not teachers
        $allUsers = $this->em->getRepository(User::class)->findAll();
        $groups = [];
        foreach ($allUsers as $u) {
            if (!in_array('ROLE_TEACHER', $u->getRoles(), true)) {
                if ($u->getStudentGroup()) {
                    $parts = preg_split('/[,/\-\s]+/', $u->getStudentGroup());
                    foreach ($parts as $p) {
                        $p = trim($p);
                        if ($p !== '') {
                            $groups[] = $p;
                        }
                    }
                }
            }
        }

        $groups = array_values(array_unique($groups));
        sort($groups);

        return new JsonResponse($groups);
    }

    #[Route('/api/teacher/courses/{id}/preview-email', name: 'api_teacher_course_email_preview', methods: ['POST'])]
    public function previewEmail(int $id, Request $request): JsonResponse
    {
        $this->checkTeacherAccess();

        $course = $this->em->getRepository(Course::class)->find($id);
        if (!$course) {
            throw new NotFoundHttpException('Cours introuvable.');
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $subject = $data['subject'] ?? '';
        $content = $data['content'] ?? '';
        $sessionId = isset($data['session_id']) ? (int) $data['session_id'] : null;
        $variables = $data['variables'] ?? [];

        $session = null;
        if ($sessionId) {
            $session = $this->em->getRepository(Session::class)->find($sessionId);
        }

        // Find a representative student to preview
        $allUsers = $this->em->getRepository(User::class)->findAll();
        $sampleStudent = null;
        foreach ($allUsers as $u) {
            if (!in_array('ROLE_TEACHER', $u->getRoles(), true)) {
                $sampleStudent = $u;
                break;
            }
        }

        $studentName = $sampleStudent ? $sampleStudent->getName() : 'Jean Dupont';
        $studentEmail = $sampleStudent ? $sampleStudent->getEmail() : 'jean.dupont@etu.univ.fr';

        $compiledSubject = $this->compileText($subject, $studentName, $course, $session, $variables);
        $compiledContent = $this->compileText($content, $studentName, $course, $session, $variables);

        return new JsonResponse([
            'subject' => $compiledSubject,
            'content' => $compiledContent,
            'sampleStudent' => [
                'name' => $studentName,
                'email' => $studentEmail,
            ],
        ]);
    }

    #[Route('/api/teacher/courses/{id}/send-email', name: 'api_teacher_course_email_send', methods: ['POST'])]
    public function sendEmail(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->checkTeacherAccess();

        $course = $this->em->getRepository(Course::class)->find($id);
        if (!$course) {
            throw new NotFoundHttpException('Cours introuvable.');
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $subject = $data['subject'] ?? '';
        $content = $data['content'] ?? '';
        $sessionId = isset($data['session_id']) ? (int) $data['session_id'] : null;
        $targetGroup = $data['targetGroup'] ?? 'ALL';
        $variables = $data['variables'] ?? [];

        $session = null;
        if ($sessionId) {
            $session = $this->em->getRepository(Session::class)->find($sessionId);
        }

        // Get all student users
        $allUsers = $this->em->getRepository(User::class)->findAll();
        $targetStudents = [];
        foreach ($allUsers as $u) {
            if (!in_array('ROLE_TEACHER', $u->getRoles(), true)) {
                if ($targetGroup === 'ALL') {
                    $targetStudents[] = $u;
                } else {
                    $studentGroup = $u->getStudentGroup() ?? '';
                    // check if studentGroup contains the target group
                    if (str_contains($studentGroup, $targetGroup)) {
                        $targetStudents[] = $u;
                    }
                }
            }
        }

        if (count($targetStudents) === 0) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Aucun étudiant correspondant au groupe ciblé.',
                'recipientsCount' => 0,
            ], 400);
        }

        $sentCount = 0;
        foreach ($targetStudents as $student) {
            $compiledSubject = $this->compileText($subject, $student->getName(), $course, $session, $variables);
            $compiledContent = $this->compileText($content, $student->getName(), $course, $session, $variables);

            try {
                $emailObj = (new Email())
                    ->from('no-reply@progressia.app')
                    ->to($student->getEmail())
                    ->subject($compiledSubject)
                    ->html(nl2br($compiledContent));

                $this->mailer->send($emailObj);
                $sentCount++;
            } catch (\Exception $e) {
                // In dev with null://null, it shouldn't fail, but we catch exceptions just in case.
                // Log or track failure if needed.
            }
        }

        // Save history
        $sentEmail = new SentEmail();
        $sentEmail->setCourse($course);
        if ($session) {
            $sentEmail->setSession($session);
        }
        $sentEmail->setSubject($subject);
        $sentEmail->setContent($content);
        $sentEmail->setSender($currentUser);
        $sentEmail->setTargetGroup($targetGroup);
        $sentEmail->setRecipientsCount(count($targetStudents));
        $sentEmail->setVariables($variables);

        $this->em->persist($sentEmail);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'recipientsCount' => count($targetStudents),
            'sentCount' => $sentCount,
        ]);
    }

    private function compileText(string $text, string $studentName, Course $course, ?Session $session, array $variables): string
    {
        $replacements = [
            '{nom_etudiant}' => $studentName,
            '{titre_cours}' => $course->getTitle(),
            '{titre_seance}' => $session ? $session->getTitle() : '',
        ];

        foreach ($variables as $key => $val) {
            $replacements['{' . $key . '}'] = $val;
        }

        return strtr($text, $replacements);
    }
}
