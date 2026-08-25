<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260825130033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE answer_choice ADD next_question_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE answer_choice ADD CONSTRAINT FK_335260351CF5F25E FOREIGN KEY (next_question_id) REFERENCES question (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_335260351CF5F25E ON answer_choice (next_question_id)');
        $this->addSql('ALTER TABLE evaluation ADD type VARCHAR(20) DEFAULT \'linear\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE answer_choice DROP FOREIGN KEY FK_335260351CF5F25E');
        $this->addSql('DROP INDEX IDX_335260351CF5F25E ON answer_choice');
        $this->addSql('ALTER TABLE answer_choice DROP next_question_id');
        $this->addSql('ALTER TABLE evaluation DROP type');
    }
}
