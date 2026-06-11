<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260610204818 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE answer_choice (id INT AUTO_INCREMENT NOT NULL, text VARCHAR(500) NOT NULL, correct TINYINT DEFAULT 0 NOT NULL, position INT DEFAULT 0 NOT NULL, question_id INT NOT NULL, INDEX IDX_335260351E27F6BF (question_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE badge (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(60) NOT NULL, label VARCHAR(120) NOT NULL, icon VARCHAR(16) NOT NULL, description VARCHAR(200) DEFAULT NULL, awarded_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_FEF0481DA76ED395 (user_id), UNIQUE INDEX UNIQ_BADGE_USER_CODE (user_id, code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE content_page (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, points INT DEFAULT 5 NOT NULL, position INT DEFAULT 0 NOT NULL, chapter_id INT NOT NULL, INDEX IDX_D9685BE5579F4768 (chapter_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, points_reward INT DEFAULT 20 NOT NULL, position INT DEFAULT 0 NOT NULL, chapter_id INT NOT NULL, INDEX IDX_1323A575579F4768 (chapter_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evaluation_attempt (id INT AUTO_INCREMENT NOT NULL, score INT NOT NULL, max_score INT NOT NULL, passed TINYINT NOT NULL, answers JSON NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, evaluation_id INT NOT NULL, INDEX IDX_3BFF1AECA76ED395 (user_id), INDEX IDX_3BFF1AEC456C5646 (evaluation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_completion (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, page_id INT NOT NULL, INDEX IDX_333606E0A76ED395 (user_id), INDEX IDX_333606E0C4663E4 (page_id), UNIQUE INDEX UNIQ_COMPLETION_USER_PAGE (user_id, page_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(10) DEFAULT \'qcm\' NOT NULL, statement LONGTEXT NOT NULL, multiple TINYINT DEFAULT 0 NOT NULL, points INT DEFAULT 1 NOT NULL, position INT DEFAULT 0 NOT NULL, evaluation_id INT NOT NULL, INDEX IDX_B6F7494E456C5646 (evaluation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE answer_choice ADD CONSTRAINT FK_335260351E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE badge ADD CONSTRAINT FK_FEF0481DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_page ADD CONSTRAINT FK_D9685BE5579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation_attempt ADD CONSTRAINT FK_3BFF1AECA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation_attempt ADD CONSTRAINT FK_3BFF1AEC456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_completion ADD CONSTRAINT FK_333606E0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_completion ADD CONSTRAINT FK_333606E0C4663E4 FOREIGN KEY (page_id) REFERENCES content_page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chapter DROP content');
        $this->addSql('ALTER TABLE course ADD category VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE answer_choice DROP FOREIGN KEY FK_335260351E27F6BF');
        $this->addSql('ALTER TABLE badge DROP FOREIGN KEY FK_FEF0481DA76ED395');
        $this->addSql('ALTER TABLE content_page DROP FOREIGN KEY FK_D9685BE5579F4768');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575579F4768');
        $this->addSql('ALTER TABLE evaluation_attempt DROP FOREIGN KEY FK_3BFF1AECA76ED395');
        $this->addSql('ALTER TABLE evaluation_attempt DROP FOREIGN KEY FK_3BFF1AEC456C5646');
        $this->addSql('ALTER TABLE page_completion DROP FOREIGN KEY FK_333606E0A76ED395');
        $this->addSql('ALTER TABLE page_completion DROP FOREIGN KEY FK_333606E0C4663E4');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E456C5646');
        $this->addSql('DROP TABLE answer_choice');
        $this->addSql('DROP TABLE badge');
        $this->addSql('DROP TABLE content_page');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('DROP TABLE evaluation_attempt');
        $this->addSql('DROP TABLE page_completion');
        $this->addSql('DROP TABLE question');
        $this->addSql('ALTER TABLE chapter ADD content LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE course DROP category');
    }
}
