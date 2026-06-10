<?php

namespace App\DataFixtures;

use App\Entity\Chapter;
use App\Entity\Choice;
use App\Entity\Course;
use App\Entity\Evaluation;
use App\Entity\Page;
use App\Entity\Question;
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
        $this->loadMockCourses($manager);
        $this->loadDemoCourses($manager);

        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
    {
        $teacher = (new User())->setEmail('teacher@pedagoflow.test')->setName('Prof. Démo')->setRoles(['ROLE_TEACHER']);
        $teacher->setPassword($this->hasher->hashPassword($teacher, 'teacher'));
        $manager->persist($teacher);

        $student = (new User())->setEmail('student@pedagoflow.test')->setName('Élève Démo')->setRoles(['ROLE_STUDENT']);
        $student->setPassword($this->hasher->hashPassword($student, 'student'));
        $manager->persist($student);
    }

    private function loadMockCourses(ObjectManager $manager): void
    {
        $path = __DIR__.'/data/mock-courses.json';
        $data = json_decode((string) file_get_contents($path), true, 512, \JSON_THROW_ON_ERROR);
        // map legacy ids to categories
        $categories = ['php-oop-example' => 'back'];

        foreach ($data['courses'] ?? [] as $c) {
            $course = (new Course())
                ->setTitle($c['title'] ?? 'Sans titre')
                ->setTheme($c['theme'] ?? null)
                ->setCategory($categories[$c['id'] ?? ''] ?? 'other')
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
                    $chapter = (new Chapter())->setTitle($ch['title'] ?? 'Chapitre')->setPosition($chi);
                    $session->addChapter($chapter);
                    $manager->persist($chapter);

                    // legacy chapter content becomes a single content page
                    $page = (new Page())->setTitle('Contenu')->setContent($ch['content'] ?? '')->setPosition(0);
                    $chapter->addPage($page);
                    $manager->persist($page);
                }
            }

            // add a demo evaluation to the Back course
            if (($c['id'] ?? '') === 'php-oop-example') {
                $firstChapter = $course->getSessions()->first()->getChapters()->first();
                $this->addPhpEvaluation($manager, $firstChapter);
            }
        }
    }

    private function loadDemoCourses(ObjectManager $manager): void
    {
        // FRONT course
        $front = (new Course())
            ->setTitle('Vue 3 — Composants & réactivité')->setTheme('Frontend')->setCategory('front')
            ->setContext('Développement Front')->setAccentColor('#06b6d4')->setLevel('Intermédiaire')
            ->setScenario('Construire des interfaces réactives avec Vue 3 et la Composition API');
        $manager->persist($front);
        $s = (new Session())->setTitle('Séance 1 — Les bases de Vue')->setPitch('Composants, props, réactivité')->setPosition(0)->setRenderConfig(self::DEFAULT_RENDER_CONFIG);
        $front->addSession($s);
        $manager->persist($s);
        $ch = (new Chapter())->setTitle('Réactivité')->setPosition(0);
        $s->addChapter($ch);
        $manager->persist($ch);
        foreach ([
            ['ref vs reactive', "# Réactivité\n`ref()` enveloppe une valeur ; `reactive()` rend un objet réactif. Dans le template, les refs sont déballées automatiquement."],
            ['Composants', "# Composants\nUn composant `.vue` regroupe template, script et style. Les **props** descendent, les **events** remontent."],
        ] as $i => [$title, $content]) {
            $p = (new Page())->setTitle($title)->setContent($content)->setPosition($i);
            $ch->addPage($p);
            $manager->persist($p);
        }
        $this->addVueEvaluation($manager, $ch);

        // FULLSTACK course
        $full = (new Course())
            ->setTitle('Symfony + Vue — Application fullstack')->setTheme('Fullstack')->setCategory('fullstack')
            ->setContext('Développement Fullstack')->setAccentColor('#8b5cf6')->setLevel('Avancé')
            ->setScenario('Relier une API Symfony à un front Vue avec authentification JWT');
        $manager->persist($full);
        $s2 = (new Session())->setTitle('Séance 1 — API découplée')->setPitch('REST, JWT, CORS')->setPosition(0)->setRenderConfig(self::DEFAULT_RENDER_CONFIG);
        $full->addSession($s2);
        $manager->persist($s2);
        $ch2 = (new Chapter())->setTitle('Architecture')->setPosition(0);
        $s2->addChapter($ch2);
        $manager->persist($ch2);
        $p2 = (new Page())->setTitle('API découplée')->setContent("# API découplée\nLe front (Vue) consomme une API JSON (Symfony + API Platform). L'auth se fait par **JWT** ; le CORS autorise l'origine du front.")->setPosition(0);
        $ch2->addPage($p2);
        $manager->persist($p2);
    }

    private function addPhpEvaluation(ObjectManager $manager, Chapter $chapter): void
    {
        $eval = (new Evaluation())->setTitle('Quiz — POO en PHP')->setDescription('Vérifiez vos bases de la POO.')->setPointsReward(30)->setPosition(0);
        $chapter->addEvaluation($eval);
        $manager->persist($eval);

        $q1 = (new Question())->setType(Question::TYPE_QCM)->setStatement('Quel mot-clé instancie un objet en PHP ?')->setPoints(2)->setPosition(0);
        $eval->addQuestion($q1);
        $manager->persist($q1);
        foreach ([['new', true], ['create', false], ['instance', false], ['object', false]] as $i => [$t, $ok]) {
            $c = (new Choice())->setText($t)->setCorrect($ok)->setPosition($i);
            $q1->addChoice($c);
            $manager->persist($c);
        }

        $q2 = (new Question())->setType(Question::TYPE_QCM)->setStatement('Quelles visibilités existent en PHP ? (plusieurs réponses)')->setMultiple(true)->setPoints(3)->setPosition(1);
        $eval->addQuestion($q2);
        $manager->persist($q2);
        foreach ([['public', true], ['private', true], ['protected', true], ['internal', false]] as $i => [$t, $ok]) {
            $c = (new Choice())->setText($t)->setCorrect($ok)->setPosition($i);
            $q2->addChoice($c);
            $manager->persist($c);
        }

        $q3 = (new Question())->setType(Question::TYPE_FREE)->setStatement('Expliquez en une phrase la différence entre une classe et un objet.')->setPoints(2)->setPosition(2);
        $eval->addQuestion($q3);
        $manager->persist($q3);
    }

    private function addVueEvaluation(ObjectManager $manager, Chapter $chapter): void
    {
        $eval = (new Evaluation())->setTitle('Quiz — Vue 3')->setDescription('Les fondamentaux de Vue.')->setPointsReward(25)->setPosition(0);
        $chapter->addEvaluation($eval);
        $manager->persist($eval);

        $q1 = (new Question())->setType(Question::TYPE_QCM)->setStatement('Quelle fonction crée une référence réactive ?')->setPoints(2)->setPosition(0);
        $eval->addQuestion($q1);
        $manager->persist($q1);
        foreach ([['ref()', true], ['useState()', false], ['signal()', false], ['watch()', false]] as $i => [$t, $ok]) {
            $c = (new Choice())->setText($t)->setCorrect($ok)->setPosition($i);
            $q1->addChoice($c);
            $manager->persist($c);
        }

        $q2 = (new Question())->setType(Question::TYPE_FREE)->setStatement('À quoi sert une prop dans un composant ?')->setPoints(1)->setPosition(1);
        $eval->addQuestion($q2);
        $manager->persist($q2);
    }
}
