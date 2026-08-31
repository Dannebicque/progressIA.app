<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:diagnose-schema',
    description: 'Diagnostique l’introspection du schéma Doctrine table par table.',
)]
final class DiagnoseSchemaCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $database = (string) $this->connection->fetchOne('SELECT DATABASE()');
        $version = (string) $this->connection->fetchOne('SELECT VERSION()');

        $io->title('Diagnostic du schéma Doctrine');
        $io->definitionList(
            ['Base' => $database],
            ['Serveur' => $version],
            ['Plateforme Doctrine' => $this->connection->getDatabasePlatform()::class],
        );

        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_COMMENT
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            ORDER BY TABLE_NAME
            SQL);

        $metadataByTable = [];

        foreach ($rows as $row) {
            $tableName = (string) $row['TABLE_NAME'];
            $metadataByTable[$tableName] = $row;
        }

        $io->section(sprintf('Objets trouvés dans information_schema (%d)', count($rows)));
        $io->table(
            ['Nom', 'Type', 'Moteur', 'Commentaire'],
            array_map(
                static fn (array $row): array => [
                    (string) $row['TABLE_NAME'],
                    (string) $row['TABLE_TYPE'],
                    $row['ENGINE'] ?? 'NULL',
                    $row['TABLE_COMMENT'] ?? '',
                ],
                $rows,
            ),
        );

        $schemaManager = $this->connection->createSchemaManager();
        $tableNames = $schemaManager->listTableNames();
        $failures = [];

        $io->section(sprintf('Introspection Doctrine table par table (%d)', count($tableNames)));

        foreach ($tableNames as $tableName) {
            $output->write(sprintf('  %-50s ', $tableName));

            try {
                $schemaManager->introspectTable($tableName);
                $output->writeln('<info>OK</info>');
            } catch (Throwable $exception) {
                $output->writeln('<error>ECHEC</error>');

                $metadata = $metadataByTable[$tableName] ?? null;
                $failures[] = [
                    'table' => $tableName,
                    'type' => $metadata['TABLE_TYPE'] ?? '?',
                    'engine' => $metadata['ENGINE'] ?? 'NULL',
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        if ($failures === []) {
            $io->success('Doctrine parvient à introspecter toutes les tables.');

            return Command::SUCCESS;
        }

        $io->error(sprintf('%d table(s) provoquent une erreur d’introspection Doctrine.', count($failures)));

        foreach ($failures as $failure) {
            $io->section((string) $failure['table']);
            $io->definitionList(
                ['Type' => (string) $failure['type']],
                ['Moteur' => (string) $failure['engine']],
                ['Exception' => (string) $failure['exception']],
                ['Message' => (string) $failure['message']],
            );
        }

        return Command::FAILURE;
    }
}
