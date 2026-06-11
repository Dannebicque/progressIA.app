<?php

namespace App\Service;

use App\Entity\Badge;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\EvaluationAttemptRepository;
use App\Repository\PageCompletionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Awards points and (re)evaluates the badge catalog.
 *
 * Badge rules are idempotent: recheckBadges() can be called after any action and
 * only grants badges the user does not already have.
 */
final class GamificationService
{
    /** points threshold => [icon, label] */
    private const POINT_MILESTONES = [
        50 => ['🌱', 'Première étincelle'],
        150 => ['🔥', 'En feu'],
        300 => ['⚡', 'Survolté'],
        600 => ['🏆', 'Champion'],
        1000 => ['👑', 'Légende vivante'],
    ];

    /** course category => badge definition */
    private const CATEGORY_BADGES = [
        'back' => ['code' => 'cat-back', 'icon' => '🛡️', 'label' => 'Maître du Back', 'desc' => 'Terminer un cours Back de bout en bout'],
        'front' => ['code' => 'cat-front', 'icon' => '🎨', 'label' => 'Virtuose du Front', 'desc' => 'Terminer un cours Front, pixel perfect'],
        'fullstack' => ['code' => 'cat-fullstack', 'icon' => '🚀', 'label' => 'Légende Fullstack', 'desc' => 'Terminer un cours Fullstack'],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CourseRepository $courses,
        private readonly PageCompletionRepository $completions,
        private readonly EvaluationAttemptRepository $attempts,
    ) {
    }

    public function awardPoints(User $user, int $points): void
    {
        if ($points > 0) {
            $user->addPoints($points);
        }
    }

    /**
     * Re-evaluate every badge rule for the user.
     *
     * @return list<Badge> badges newly awarded during this call
     */
    public function recheckBadges(User $user): array
    {
        $new = [];

        // 1. Points milestones
        foreach (self::POINT_MILESTONES as $threshold => [$icon, $label]) {
            $code = 'pts-'.$threshold;
            if ($user->getPoints() >= $threshold && !$user->hasBadge($code)) {
                $new[] = $this->award($user, $code, $icon, $label, "Atteindre $threshold points");
            }
        }

        // 2. Course completion by category (all pages of the course completed)
        $completed = array_flip($this->completions->completedPageIds($user));
        $doneCategories = [];
        foreach ($this->courses->findAll() as $course) {
            $pageIds = $this->coursePageIds($course);
            if (!$pageIds) {
                continue;
            }
            $allDone = true;
            foreach ($pageIds as $pid) {
                if (!isset($completed[$pid])) {
                    $allDone = false;
                    break;
                }
            }
            if (!$allDone) {
                continue;
            }
            $cat = strtolower((string) $course->getCategory());
            $doneCategories[$cat] = true;
            if (isset(self::CATEGORY_BADGES[$cat])) {
                $b = self::CATEGORY_BADGES[$cat];
                if (!$user->hasBadge($b['code'])) {
                    $new[] = $this->award($user, $b['code'], $b['icon'], $b['label'], $b['desc']);
                }
            }
        }

        // 3. Fullstack combo: a Back course AND a Front course completed
        if (isset($doneCategories['back'], $doneCategories['front']) && !$user->hasBadge('combo-fullstack')) {
            $new[] = $this->award($user, 'combo-fullstack', '🌐', 'Touche-à-tout', 'Un cours Back ET un cours Front terminés');
        }

        // 4. Evaluation badges
        $anyPassed = false;
        $anyPerfect = false;
        foreach ($this->attempts->forUser($user) as $a) {
            if ($a->isPassed()) {
                $anyPassed = true;
            }
            if ($a->getMaxScore() > 0 && $a->getScore() === $a->getMaxScore()) {
                $anyPerfect = true;
            }
        }
        if ($anyPassed && !$user->hasBadge('eval-first')) {
            $new[] = $this->award($user, 'eval-first', '✅', 'Premier quiz validé', 'Réussir une première évaluation');
        }
        if ($anyPerfect && !$user->hasBadge('eval-perfect')) {
            $new[] = $this->award($user, 'eval-perfect', '🎯', 'Sans faute', 'Décrocher un score parfait à une évaluation');
        }

        return $new;
    }

    /** @return list<int> */
    private function coursePageIds(Course $course): array
    {
        $ids = [];
        foreach ($course->getSessions() as $session) {
            foreach ($session->getChapters() as $chapter) {
                foreach ($chapter->getPages() as $page) {
                    $ids[] = $page->getId();
                }
            }
        }

        return $ids;
    }

    private function award(User $user, string $code, string $icon, string $label, ?string $desc): Badge
    {
        $badge = (new Badge())
            ->setCode($code)
            ->setIcon($icon)
            ->setLabel($label)
            ->setDescription($desc);
        $user->addBadge($badge);
        $this->em->persist($badge);

        return $badge;
    }
}
