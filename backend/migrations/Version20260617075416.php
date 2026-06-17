<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260617075416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chapter ADD visible TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE content_page ADD visible TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE course ADD semester VARCHAR(50) DEFAULT NULL, ADD visible TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE evaluation ADD visible TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE session ADD visible TINYINT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chapter DROP visible');
        $this->addSql('ALTER TABLE content_page DROP visible');
        $this->addSql('ALTER TABLE course DROP semester, DROP visible');
        $this->addSql('ALTER TABLE evaluation DROP visible');
        $this->addSql('ALTER TABLE session DROP visible');
    }
}
