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
        $this->loadBatmanCourse($manager);

        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
    {
        $teacher = (new User())->setEmail('teacher@pedagoflow.test')->setName('Prof. Démo')->setRoles(['ROLE_TEACHER']);
        $teacher->setPassword($this->hasher->hashPassword($teacher, 'teacher'));
        $manager->persist($teacher);

        $student = (new User())
            ->setEmail('student@pedagoflow.test')
            ->setName('Élève Démo')
            ->setRoles(['ROLE_STUDENT'])
            ->setStudentGroup('TD1 - TP2')
            ->setStudentYear('BUT2 - MMI')
            ->setStudentInstitution('IUT de Bordeaux');
        $student->setPassword($this->hasher->hashPassword($student, 'student'));
        $manager->persist($student);
    }

    private function loadMockCourses(ObjectManager $manager): void
    {
        $path = __DIR__.'/data/mock-courses.json';
        $data = json_decode((string) file_get_contents($path), true, 512, \JSON_THROW_ON_ERROR);
        // map legacy ids to categories
        $categories = ['php-oop-example' => 'back'];

        $i = 10;
        foreach ($data['courses'] ?? [] as $c) {
            $course = (new Course())
                ->setTitle($c['title'] ?? 'Sans titre')
                ->setTheme($c['theme'] ?? null)
                ->setCategory($categories[$c['id'] ?? ''] ?? 'other')
                ->setContext($c['context'] ?? null)
                ->setAccentColor($c['accentColor'] ?? null)
                ->setLevel($c['level'] ?? null)
                ->setScenario($c['scenario'] ?? null)
                ->setUpdatedAt((new \DateTimeImmutable())->modify("-{$i} days"));
            $i++;
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
            ->setScenario('Construire des interfaces réactives avec Vue 3 et la Composition API')
            ->setUpdatedAt((new \DateTimeImmutable())->modify('-5 hours'));
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
            ->setScenario('Relier une API Symfony à un front Vue avec authentification JWT')
            ->setUpdatedAt((new \DateTimeImmutable())->modify('-2 hours'));
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

    // ---- Example course: building the BATMAN CORP shop in Symfony, narrated by Alfred ----

    private function loadBatmanCourse(ObjectManager $manager): void
    {
        $course = (new Course())
            ->setTitle('Création de la boutique BATMAN CORP')
            ->setTheme('Développement Symfony')
            ->setCategory('back')
            ->setContext('Projet fil rouge — BUT2 MMI')
            ->setAccentColor('#f59e0b')
            ->setLevel('BUT2')
            ->setScenario("Accompagné par Alfred, le majordome de Wayne Manor, démarrez la boutique en ligne de Batman Corp en Symfony : projet, entité, base de données, catalogue.")
            ->setUpdatedAt((new \DateTimeImmutable())->modify('-1 hours'));
        $manager->persist($course);

        $session = $this->makeSession($manager, $course, 0, 'Séance 1 — Démarrer la boutique en Symfony', 'Du projet vide au premier catalogue, guidé par Alfred.');

        // Chapitre 1
        $c1 = $this->makeChapter($manager, $session, 0, 'Le briefing');
        $this->makePage($manager, $c1, 0, 'Rencontre avec Alfred', <<<'MD'
# Bienvenue au manoir Wayne

🦇 **Le contexte.** Maître Wayne veut une boutique en ligne pour **Batman Corp**, à développer en **Symfony**. Alfred vous accompagnera tout au long du projet.

## Objectifs de la séance
- Créer le projet Symfony
- Découvrir la structure des dossiers
- Lancer le serveur de développement

## 1. Créer le projet
```bash
symfony new batman-corp --webapp
cd batman-corp
symfony server:start
```

## 2. La structure du projet
| Dossier | Rôle |
|---|---|
| `src/` | Entités, contrôleurs, services |
| `templates/` | Vues Twig |
| `config/` | Configuration |
| `public/` | Point d'entrée web |

> 💡 **Conseil d'Alfred** : « Chaque chose à sa place, Monsieur. Un projet rangé est un projet maîtrisé. »
MD);

        // Chapitre 2
        $c2 = $this->makeChapter($manager, $session, 1, "L'entité Produit");
        $this->makePage($manager, $c2, 0, 'Modéliser les gadgets', <<<'MD'
# L'entité Produit

🦇 **Mission.** Modéliser un gadget de l'arsenal : un **nom**, un **prix**, un **stock**.

## 1. Générer l'entité
```bash
php bin/console make:entity Product
```

## 2. Les champs
```php
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private float $price;

    #[ORM\Column]
    private int $stock = 0;
}
```

## À retenir
- Une **entité** correspond à une **table** en base de données.
- Chaque attribut `#[ORM\Column]` décrit une **colonne**.

> 💡 **Conseil d'Alfred** : « N'oubliez pas le `stock`, Monsieur. Une rupture de Batarang en pleine patrouille serait fâcheuse. »
MD);

        // Chapitre 3
        $c3 = $this->makeChapter($manager, $session, 2, 'La base de données');
        $this->makePage($manager, $c3, 0, 'Préparer la Batcave (migrations)', <<<'MD'
# La base de données

🦇 **Étape.** Connecter le projet à une base et y créer la table `product`.

## 1. Configurer l'accès (`.env`)
```bash
DATABASE_URL="mysql://root:root@127.0.0.1:3306/batman_corp"
```

## 2. Générer et appliquer la migration
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

## 3. Garnir les rayons (fixtures)
```bash
php bin/console doctrine:fixtures:load
```

## À retenir
- `make:migration` génère le **SQL** à partir de vos entités.
- `doctrine:migrations:migrate` l'**applique** à la base.

> 💡 **Conseil d'Alfred** : « Testez toujours sur des données factices avant l'ouverture, Monsieur. »
MD);

        // Chapitre 4 (+ évaluation)
        $c4 = $this->makeChapter($manager, $session, 3, 'Afficher le catalogue');
        $this->makePage($manager, $c4, 0, 'Le contrôleur du catalogue', <<<'MD'
# Afficher le catalogue

🦇 **Objectif.** Afficher la liste des produits sur une page `/catalogue`.

## 1. Créer le contrôleur
```bash
php bin/console make:controller CatalogController
```

## 2. La route et l'action
```php
#[Route('/catalogue', name: 'catalog')]
public function index(ProductRepository $products): Response
{
    return $this->render('catalog/index.html.twig', [
        'products' => $products->findAll(),
    ]);
}
```

## 3. La vue Twig
```twig
{% for product in products %}
  <article class="card">
    <h2>{{ product.name }}</h2>
    <p>{{ product.price }} €</p>
  </article>
{% endfor %}
```

## À retenir
- Le contrôleur reçoit la requête et renvoie une `Response`.
- Le `ProductRepository` est **injecté** automatiquement par Symfony.

> 💡 **Conseil d'Alfred** : « Sobre et élégant — comme la nuit, Monsieur. Validez ce chapitre, puis prouvez vos acquis dans le quiz ci-dessous. »
MD);

        $eval = $this->makeEvaluation($manager, $c4, 1, 'Quiz — Démarrage Symfony', "Alfred évalue vos premiers pas.", 30);
        $this->makeQcm($manager, $eval, 0, 'Quelle commande crée une entité Doctrine ?', 2, false, [
            'php bin/console make:entity' => true,
            'symfony new entity' => false,
            'composer require entity' => false,
            'php bin/console new:entity' => false,
        ]);
        $this->makeQcm($manager, $eval, 1, 'Quel dossier contient les contrôleurs ?', 1, false, [
            'src/Controller' => true,
            'templates/' => false,
            'config/' => false,
            'public/' => false,
        ]);
        $this->makeQcm($manager, $eval, 2, 'Quelles commandes gèrent les migrations ? (plusieurs réponses)', 2, true, [
            'make:migration' => true,
            'doctrine:migrations:migrate' => true,
            'make:controller' => false,
            'cache:clear' => false,
        ]);
        $this->makeFree($manager, $eval, 3, 'Pourquoi est-il important de stocker le `stock` d\'un produit ?', 2);
    }

    // ---- fixture builders ----

    private function makeSession(ObjectManager $m, Course $course, int $pos, string $title, ?string $pitch): Session
    {
        $s = (new Session())->setTitle($title)->setPitch($pitch)->setPosition($pos)->setRenderConfig(self::DEFAULT_RENDER_CONFIG);
        $course->addSession($s);
        $m->persist($s);

        return $s;
    }

    private function makeChapter(ObjectManager $m, Session $session, int $pos, string $title): Chapter
    {
        $ch = (new Chapter())->setTitle($title)->setPosition($pos);
        $session->addChapter($ch);
        $m->persist($ch);

        return $ch;
    }

    private function makePage(ObjectManager $m, Chapter $chapter, int $pos, string $title, string $content, int $points = 5): Page
    {
        $p = (new Page())->setTitle($title)->setContent($content)->setPoints($points)->setPosition($pos);
        $chapter->addPage($p);
        $m->persist($p);

        return $p;
    }

    private function makeEvaluation(ObjectManager $m, Chapter $chapter, int $pos, string $title, ?string $desc, int $reward): Evaluation
    {
        $e = (new Evaluation())->setTitle($title)->setDescription($desc)->setPointsReward($reward)->setPosition($pos);
        $chapter->addEvaluation($e);
        $m->persist($e);

        return $e;
    }

    /** @param array<string, bool> $choices text => isCorrect */
    private function makeQcm(ObjectManager $m, Evaluation $eval, int $pos, string $statement, int $points, bool $multiple, array $choices): Question
    {
        $q = (new Question())->setType(Question::TYPE_QCM)->setStatement($statement)->setPoints($points)->setMultiple($multiple)->setPosition($pos);
        $eval->addQuestion($q);
        $m->persist($q);
        $i = 0;
        foreach ($choices as $text => $ok) {
            $c = (new Choice())->setText((string) $text)->setCorrect($ok)->setPosition($i++);
            $q->addChoice($c);
            $m->persist($c);
        }

        return $q;
    }

    private function makeFree(ObjectManager $m, Evaluation $eval, int $pos, string $statement, int $points): Question
    {
        $q = (new Question())->setType(Question::TYPE_FREE)->setStatement($statement)->setPoints($points)->setPosition($pos);
        $eval->addQuestion($q);
        $m->persist($q);

        return $q;
    }
}
