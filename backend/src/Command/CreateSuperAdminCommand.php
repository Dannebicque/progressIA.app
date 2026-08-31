<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Purger la base de données puis créer le super-administrateur.',
)]
class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'L\'adresse email du Super Admin')
            ->addArgument('name', InputArgument::OPTIONAL, 'Le nom du Super Admin', 'Super Admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $name = $input->getArgument('name');

        $io->title('Initialisation de la base de données & Création du Super Admin');

        // 1. Purge de la base de données
        $io->section('Purge de la base de données en cours...');
        
        try {
            $connection = $this->em->getConnection();
            $platform = $connection->getDatabasePlatform();
            
            // Récupérer le gestionnaire de schéma (compatible DBAL 3 et 4)
            if (method_exists($connection, 'createSchemaManager')) {
                $schemaManager = $connection->createSchemaManager();
            } else {
                // @phpstan-ignore-next-line
                $schemaManager = $connection->getSchemaManager();
            }
            
            $tables = $schemaManager->listTableNames();

            $platformName = strtolower($platform::class);
            $isMySQL = str_contains($platformName, 'mysql') || str_contains($platformName, 'mariadb');
            $isPostgreSQL = str_contains($platformName, 'postgresql');
            $isSQLite = str_contains($platformName, 'sqlite');

            if ($isMySQL) {
                $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            } elseif ($isSQLite) {
                $connection->executeStatement('PRAGMA foreign_keys = OFF');
            }

            foreach ($tables as $table) {
                // Ne pas purger l'historique des migrations
                if ($table === 'doctrine_migration_versions') {
                    continue;
                }

                $io->text(sprintf('  -> Vidage de la table: %s', $table));

                if ($isSQLite) {
                    $connection->executeStatement(sprintf('DELETE FROM "%s"', $table));
                    $connection->executeStatement(sprintf('DELETE FROM sqlite_sequence WHERE name="%s"', $table));
                } elseif ($isPostgreSQL) {
                    $connection->executeStatement(sprintf('TRUNCATE TABLE "%s" CASCADE', $table));
                } else {
                    $connection->executeStatement(sprintf('TRUNCATE TABLE `%s`', $table));
                }
            }

            if ($isMySQL) {
                $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
            } elseif ($isSQLite) {
                $connection->executeStatement('PRAGMA foreign_keys = ON');
            }

            $io->success('La base de données a été purgée avec succès.');
        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de la purge de la base de données : %s', $e->getMessage()));
            return Command::FAILURE;
        }

        // 2. Création de l'utilisateur
        $io->section('Création de l\'utilisateur Super Admin...');
        
        $plainPassword = bin2hex(random_bytes(6)); // 12 caractères
        
        try {
            $superAdmin = (new User())
                ->setEmail($email)
                ->setName($name)
                ->setRoles(['ROLE_SUPER_ADMIN']);

            $superAdmin->setPassword($this->hasher->hashPassword($superAdmin, $plainPassword));

            $this->em->persist($superAdmin);
            $this->em->flush();

            $io->success(sprintf('Super Admin créé avec succès. ID: %d', $superAdmin->getId()));
        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de la création du Super Admin : %s', $e->getMessage()));
            return Command::FAILURE;
        }

        // 3. Envoi de l'email
        $io->section('Envoi du mot de passe par email...');
        
        try {
            $emailObj = (new Email())
                ->from('no-reply@progressia.app')
                ->to($email)
                ->subject('Votre compte Super Admin Progressia')
                ->html(sprintf(
                    "<p>Bonjour <strong>%s</strong>,</p><p>Votre compte super admin a été configuré avec succès.</p><p>Mot de passe temporaire : <strong>%s</strong></p><p>Veuillez modifier votre mot de passe dès votre première connexion.</p>",
                    htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                    $plainPassword
                ));

            $this->mailer->send($emailObj);
            $io->success(sprintf('L\'email contenant le mot de passe temporaire a été envoyé à %s.', $email));
        } catch (\Exception $e) {
            $io->warning(sprintf('L\'utilisateur a été créé, mais l\'envoi de l\'email a échoué. Raison : %s', $e->getMessage()));
            $io->text(sprintf('Mot de passe temporaire généré : %s', $plainPassword));
        }

        return Command::SUCCESS;
    }
}
