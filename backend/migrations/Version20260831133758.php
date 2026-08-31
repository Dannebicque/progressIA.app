<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260831133758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ai_usage_log (id INT AUTO_INCREMENT NOT NULL, feature VARCHAR(100) NOT NULL, prompt_tokens INT NOT NULL, completion_tokens INT NOT NULL, estimated_cost NUMERIC(10, 5) DEFAULT \'0.00000\' NOT NULL, created_at DATETIME NOT NULL, institution_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_B9BDAD3410405986 (institution_id), INDEX IDX_B9BDAD34A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE answer_choice (id INT AUTO_INCREMENT NOT NULL, text VARCHAR(500) NOT NULL, correct TINYINT DEFAULT 0 NOT NULL, position INT DEFAULT 0 NOT NULL, question_id INT NOT NULL, next_question_id INT DEFAULT NULL, INDEX IDX_335260351E27F6BF (question_id), INDEX IDX_335260351CF5F25E (next_question_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE badge (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(60) NOT NULL, label VARCHAR(120) NOT NULL, icon VARCHAR(16) NOT NULL, description VARCHAR(200) DEFAULT NULL, awarded_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_FEF0481DA76ED395 (user_id), UNIQUE INDEX UNIQ_BADGE_USER_CODE (user_id, code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chapter (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, position INT DEFAULT 0 NOT NULL, visible TINYINT DEFAULT 1 NOT NULL, session_id INT NOT NULL, INDEX IDX_F981B52E613FECDF (session_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_request (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, institution_name VARCHAR(150) NOT NULL, message LONGTEXT NOT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE content_page (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, points INT DEFAULT 5 NOT NULL, position INT DEFAULT 0 NOT NULL, visible TINYINT DEFAULT 1 NOT NULL, chapter_id INT NOT NULL, INDEX IDX_D9685BE5579F4768 (chapter_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE course (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, theme VARCHAR(120) DEFAULT NULL, category VARCHAR(20) DEFAULT NULL, context LONGTEXT DEFAULT NULL, accent_color VARCHAR(20) DEFAULT NULL, level VARCHAR(60) DEFAULT NULL, semester VARCHAR(50) DEFAULT NULL, visible TINYINT DEFAULT 1 NOT NULL, scenario LONGTEXT DEFAULT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE course_institution (course_id INT NOT NULL, institution_id INT NOT NULL, INDEX IDX_4EF1A831591CC992 (course_id), INDEX IDX_4EF1A83110405986 (institution_id), PRIMARY KEY (course_id, institution_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE course_semester (course_id INT NOT NULL, semester_id INT NOT NULL, INDEX IDX_5E8CE3BB591CC992 (course_id), INDEX IDX_5E8CE3BB4A798B6F (semester_id), PRIMARY KEY (course_id, semester_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE course_formation (course_id INT NOT NULL, formation_id INT NOT NULL, INDEX IDX_C2E16113591CC992 (course_id), INDEX IDX_C2E161135200282E (formation_id), PRIMARY KEY (course_id, formation_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE course_teacher (course_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_B835A339591CC992 (course_id), INDEX IDX_B835A339A76ED395 (user_id), PRIMARY KEY (course_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE email_template (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, default_target VARCHAR(50) DEFAULT NULL, course_id INT NOT NULL, session_id INT DEFAULT NULL, INDEX IDX_9C0600CA591CC992 (course_id), INDEX IDX_9C0600CA613FECDF (session_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, points_reward INT DEFAULT 20 NOT NULL, position INT DEFAULT 0 NOT NULL, visible TINYINT DEFAULT 1 NOT NULL, type VARCHAR(20) DEFAULT \'linear\' NOT NULL, chapter_id INT NOT NULL, INDEX IDX_1323A575579F4768 (chapter_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evaluation_attempt (id INT AUTO_INCREMENT NOT NULL, score INT NOT NULL, max_score INT NOT NULL, passed TINYINT NOT NULL, answers JSON NOT NULL, created_at DATETIME NOT NULL, feedback_teacher LONGTEXT DEFAULT NULL, feedback_student LONGTEXT DEFAULT NULL, user_id INT NOT NULL, evaluation_id INT NOT NULL, INDEX IDX_3BFF1AECA76ED395 (user_id), INDEX IDX_3BFF1AEC456C5646 (evaluation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE formation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, institution_id INT NOT NULL, INDEX IDX_404021BF10405986 (institution_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE institution (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, subscription_fee NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, cost_per_student NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, email_domains JSON DEFAULT NULL, invitation_code VARCHAR(50) DEFAULT NULL, ai_enabled TINYINT DEFAULT 0 NOT NULL, ai_config_type VARCHAR(20) DEFAULT \'global\' NOT NULL, ai_provider VARCHAR(30) DEFAULT \'groq\' NOT NULL, ai_model VARCHAR(100) DEFAULT \'llama-3.1-70b-versatile\' NOT NULL, ai_api_key VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_3A9F98E5BA14FCCC (invitation_code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE landing_config (id INT AUTO_INCREMENT NOT NULL, hero_title VARCHAR(255) NOT NULL, hero_subtitle VARCHAR(500) NOT NULL, plans_json JSON NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_completion (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, page_id INT NOT NULL, INDEX IDX_333606E0A76ED395 (user_id), INDEX IDX_333606E0C4663E4 (page_id), UNIQUE INDEX UNIQ_COMPLETION_USER_PAGE (user_id, page_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(10) DEFAULT \'qcm\' NOT NULL, statement LONGTEXT NOT NULL, multiple TINYINT DEFAULT 0 NOT NULL, points INT DEFAULT 1 NOT NULL, position INT DEFAULT 0 NOT NULL, file_required TINYINT DEFAULT 0 NOT NULL, evaluation_id INT NOT NULL, INDEX IDX_B6F7494E456C5646 (evaluation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE semester (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, institution_id INT NOT NULL, INDEX IDX_F7388EED10405986 (institution_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sent_email (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, sent_at DATETIME NOT NULL, target_group VARCHAR(100) NOT NULL, recipients_count INT NOT NULL, variables JSON DEFAULT \'[]\' NOT NULL, course_id INT NOT NULL, session_id INT DEFAULT NULL, sender_id INT NOT NULL, INDEX IDX_E92EE5FC591CC992 (course_id), INDEX IDX_E92EE5FC613FECDF (session_id), INDEX IDX_E92EE5FCF624B39D (sender_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE session (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, pitch LONGTEXT DEFAULT NULL, render_config JSON NOT NULL, position INT DEFAULT 0 NOT NULL, visible TINYINT DEFAULT 1 NOT NULL, course_id INT NOT NULL, INDEX IDX_D044D5D4591CC992 (course_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(120) NOT NULL, points INT DEFAULT 0 NOT NULL, avatar VARCHAR(255) DEFAULT NULL, student_group VARCHAR(50) DEFAULT NULL, student_year VARCHAR(50) DEFAULT NULL, student_institution VARCHAR(120) DEFAULT NULL, institution_id INT DEFAULT NULL, student_semester_id INT DEFAULT NULL, student_formation_id INT DEFAULT NULL, INDEX IDX_8D93D64910405986 (institution_id), INDEX IDX_8D93D6499D99866C (student_semester_id), INDEX IDX_8D93D649CBDE9999 (student_formation_id), UNIQUE INDEX UNIQ_USER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_institution (user_id INT NOT NULL, institution_id INT NOT NULL, INDEX IDX_93845170A76ED395 (user_id), INDEX IDX_9384517010405986 (institution_id), PRIMARY KEY (user_id, institution_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ai_usage_log ADD CONSTRAINT FK_B9BDAD3410405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ai_usage_log ADD CONSTRAINT FK_B9BDAD34A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE answer_choice ADD CONSTRAINT FK_335260351E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE answer_choice ADD CONSTRAINT FK_335260351CF5F25E FOREIGN KEY (next_question_id) REFERENCES question (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE badge ADD CONSTRAINT FK_FEF0481DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chapter ADD CONSTRAINT FK_F981B52E613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_page ADD CONSTRAINT FK_D9685BE5579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_institution ADD CONSTRAINT FK_4EF1A831591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_institution ADD CONSTRAINT FK_4EF1A83110405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_semester ADD CONSTRAINT FK_5E8CE3BB591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_semester ADD CONSTRAINT FK_5E8CE3BB4A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_formation ADD CONSTRAINT FK_C2E16113591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_formation ADD CONSTRAINT FK_C2E161135200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_teacher ADD CONSTRAINT FK_B835A339591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_teacher ADD CONSTRAINT FK_B835A339A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CA591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CA613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation_attempt ADD CONSTRAINT FK_3BFF1AECA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation_attempt ADD CONSTRAINT FK_3BFF1AEC456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BF10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_completion ADD CONSTRAINT FK_333606E0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_completion ADD CONSTRAINT FK_333606E0C4663E4 FOREIGN KEY (page_id) REFERENCES content_page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE semester ADD CONSTRAINT FK_F7388EED10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sent_email ADD CONSTRAINT FK_E92EE5FC591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sent_email ADD CONSTRAINT FK_E92EE5FC613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sent_email ADD CONSTRAINT FK_E92EE5FCF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64910405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D6499D99866C FOREIGN KEY (student_semester_id) REFERENCES semester (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649CBDE9999 FOREIGN KEY (student_formation_id) REFERENCES formation (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_institution ADD CONSTRAINT FK_93845170A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_institution ADD CONSTRAINT FK_9384517010405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_usage_log DROP FOREIGN KEY FK_B9BDAD3410405986');
        $this->addSql('ALTER TABLE ai_usage_log DROP FOREIGN KEY FK_B9BDAD34A76ED395');
        $this->addSql('ALTER TABLE answer_choice DROP FOREIGN KEY FK_335260351E27F6BF');
        $this->addSql('ALTER TABLE answer_choice DROP FOREIGN KEY FK_335260351CF5F25E');
        $this->addSql('ALTER TABLE badge DROP FOREIGN KEY FK_FEF0481DA76ED395');
        $this->addSql('ALTER TABLE chapter DROP FOREIGN KEY FK_F981B52E613FECDF');
        $this->addSql('ALTER TABLE content_page DROP FOREIGN KEY FK_D9685BE5579F4768');
        $this->addSql('ALTER TABLE course_institution DROP FOREIGN KEY FK_4EF1A831591CC992');
        $this->addSql('ALTER TABLE course_institution DROP FOREIGN KEY FK_4EF1A83110405986');
        $this->addSql('ALTER TABLE course_semester DROP FOREIGN KEY FK_5E8CE3BB591CC992');
        $this->addSql('ALTER TABLE course_semester DROP FOREIGN KEY FK_5E8CE3BB4A798B6F');
        $this->addSql('ALTER TABLE course_formation DROP FOREIGN KEY FK_C2E16113591CC992');
        $this->addSql('ALTER TABLE course_formation DROP FOREIGN KEY FK_C2E161135200282E');
        $this->addSql('ALTER TABLE course_teacher DROP FOREIGN KEY FK_B835A339591CC992');
        $this->addSql('ALTER TABLE course_teacher DROP FOREIGN KEY FK_B835A339A76ED395');
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CA591CC992');
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CA613FECDF');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575579F4768');
        $this->addSql('ALTER TABLE evaluation_attempt DROP FOREIGN KEY FK_3BFF1AECA76ED395');
        $this->addSql('ALTER TABLE evaluation_attempt DROP FOREIGN KEY FK_3BFF1AEC456C5646');
        $this->addSql('ALTER TABLE formation DROP FOREIGN KEY FK_404021BF10405986');
        $this->addSql('ALTER TABLE page_completion DROP FOREIGN KEY FK_333606E0A76ED395');
        $this->addSql('ALTER TABLE page_completion DROP FOREIGN KEY FK_333606E0C4663E4');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E456C5646');
        $this->addSql('ALTER TABLE semester DROP FOREIGN KEY FK_F7388EED10405986');
        $this->addSql('ALTER TABLE sent_email DROP FOREIGN KEY FK_E92EE5FC591CC992');
        $this->addSql('ALTER TABLE sent_email DROP FOREIGN KEY FK_E92EE5FC613FECDF');
        $this->addSql('ALTER TABLE sent_email DROP FOREIGN KEY FK_E92EE5FCF624B39D');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4591CC992');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64910405986');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6499D99866C');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649CBDE9999');
        $this->addSql('ALTER TABLE user_institution DROP FOREIGN KEY FK_93845170A76ED395');
        $this->addSql('ALTER TABLE user_institution DROP FOREIGN KEY FK_9384517010405986');
        $this->addSql('DROP TABLE ai_usage_log');
        $this->addSql('DROP TABLE answer_choice');
        $this->addSql('DROP TABLE badge');
        $this->addSql('DROP TABLE chapter');
        $this->addSql('DROP TABLE contact_request');
        $this->addSql('DROP TABLE content_page');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE course_institution');
        $this->addSql('DROP TABLE course_semester');
        $this->addSql('DROP TABLE course_formation');
        $this->addSql('DROP TABLE course_teacher');
        $this->addSql('DROP TABLE email_template');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('DROP TABLE evaluation_attempt');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE institution');
        $this->addSql('DROP TABLE landing_config');
        $this->addSql('DROP TABLE page_completion');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE semester');
        $this->addSql('DROP TABLE sent_email');
        $this->addSql('DROP TABLE session');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_institution');
    }
}
