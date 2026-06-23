<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260619094558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_template (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, default_target VARCHAR(50) DEFAULT NULL, course_id INT NOT NULL, session_id INT DEFAULT NULL, INDEX IDX_9C0600CA591CC992 (course_id), INDEX IDX_9C0600CA613FECDF (session_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sent_email (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, sent_at DATETIME NOT NULL, target_group VARCHAR(100) NOT NULL, recipients_count INT NOT NULL, course_id INT NOT NULL, session_id INT DEFAULT NULL, sender_id INT NOT NULL, INDEX IDX_E92EE5FC591CC992 (course_id), INDEX IDX_E92EE5FC613FECDF (session_id), INDEX IDX_E92EE5FCF624B39D (sender_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CA591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CA613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sent_email ADD CONSTRAINT FK_E92EE5FC591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sent_email ADD CONSTRAINT FK_E92EE5FC613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sent_email ADD CONSTRAINT FK_E92EE5FCF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CA591CC992');
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CA613FECDF');
        $this->addSql('ALTER TABLE sent_email DROP FOREIGN KEY FK_E92EE5FC591CC992');
        $this->addSql('ALTER TABLE sent_email DROP FOREIGN KEY FK_E92EE5FC613FECDF');
        $this->addSql('ALTER TABLE sent_email DROP FOREIGN KEY FK_E92EE5FCF624B39D');
        $this->addSql('DROP TABLE email_template');
        $this->addSql('DROP TABLE sent_email');
    }
}
