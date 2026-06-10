<?php

namespace App\DataFixtures;

use App\Entity\Chapter;
use App\Entity\Course;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const DEFAULT_RENDER_CONFIG = [
        'allowUpload' => true,
        'allowedTypes' => ['file', 'image'],
        'maxFiles' => 1,
    ];

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);
        $this->loadCourses($manager);

        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
    {
        $teacher = (new User())
            ->setEmail('teacher@pedagoflow.test')
            ->setName('Prof. Démo')
            ->setRoles(['ROLE_TEACHER']);
        $teacher->setPassword($this->hasher->hashPassword($teacher, 'teacher'));
        $manager->persist($teacher);

        $student = (new User())
            ->setEmail('student@pedagoflow.test')
            ->setName('Élève Démo')
            ->setRoles(['ROLE_STUDENT'])
            ->setPoints(120);
        $student->setPassword($this->hasher->hashPassword($student, 'student'));
        $manager->persist($student);
    }

    private function loadCourses(ObjectManager $manager): void
    {
        $path = __DIR__.'/data/mock-courses.json';
        $data = json_decode((string) file_get_contents($path), true, 512, \JSON_THROW_ON_ERROR);

        foreach ($data['courses'] ?? [] as $c) {
            $course = (new Course())
                ->setTitle($c['title'] ?? 'Sans titre')
                ->setTheme($c['theme'] ?? null)
                ->setContext($c['context'] ?? null)
                ->setAccentColor($c['accentColor'] ?? null)
                ->setLevel($c['level'] ?? null)
                ->setScenario($c['scenario'] ?? null);
            $manager->persist($course);

            foreach (array_values($c['sessions'] ?? []) as $si => $s) {
                $session = (new Session())
                    ->setTitle($s['title'] ?? 'Séance')
                    ->setPitch($s['pitch'] ?? null)
                    ->setPosition($si)
                    ->setRenderConfig(array_merge(self::DEFAULT_RENDER_CONFIG, $s['renderConfig'] ?? []));
                $course->addSession($session);
                $manager->persist($session);

                foreach (array_values($s['chapters'] ?? []) as $chi => $ch) {
                    $chapter = (new Chapter())
                        ->setTitle($ch['title'] ?? 'Chapitre')
                        ->setContent($ch['content'] ?? '')
                        ->setPosition($chi);
                    $session->addChapter($chapter);
                    $manager->persist($chapter);
                }
            }
        }
    }
}
