<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260807100807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE course_institution (course_id INT NOT NULL, institution_id INT NOT NULL, INDEX IDX_4EF1A831591CC992 (course_id), INDEX IDX_4EF1A83110405986 (institution_id), PRIMARY KEY (course_id, institution_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE course_semester (course_id INT NOT NULL, semester_id INT NOT NULL, INDEX IDX_5E8CE3BB591CC992 (course_id), INDEX IDX_5E8CE3BB4A798B6F (semester_id), PRIMARY KEY (course_id, semester_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE course_formation (course_id INT NOT NULL, formation_id INT NOT NULL, INDEX IDX_C2E16113591CC992 (course_id), INDEX IDX_C2E161135200282E (formation_id), PRIMARY KEY (course_id, formation_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE course_teacher (course_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_B835A339591CC992 (course_id), INDEX IDX_B835A339A76ED395 (user_id), PRIMARY KEY (course_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE formation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, institution_id INT NOT NULL, INDEX IDX_404021BF10405986 (institution_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE institution (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, subscription_fee NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, cost_per_student NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE semester (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, institution_id INT NOT NULL, INDEX IDX_F7388EED10405986 (institution_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_institution (user_id INT NOT NULL, institution_id INT NOT NULL, INDEX IDX_93845170A76ED395 (user_id), INDEX IDX_9384517010405986 (institution_id), PRIMARY KEY (user_id, institution_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE course_institution ADD CONSTRAINT FK_4EF1A831591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_institution ADD CONSTRAINT FK_4EF1A83110405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_semester ADD CONSTRAINT FK_5E8CE3BB591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_semester ADD CONSTRAINT FK_5E8CE3BB4A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_formation ADD CONSTRAINT FK_C2E16113591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_formation ADD CONSTRAINT FK_C2E161135200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_teacher ADD CONSTRAINT FK_B835A339591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_teacher ADD CONSTRAINT FK_B835A339A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BF10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE semester ADD CONSTRAINT FK_F7388EED10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_institution ADD CONSTRAINT FK_93845170A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_institution ADD CONSTRAINT FK_9384517010405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD institution_id INT DEFAULT NULL, ADD student_semester_id INT DEFAULT NULL, ADD student_formation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64910405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6499D99866C FOREIGN KEY (student_semester_id) REFERENCES semester (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649CBDE9999 FOREIGN KEY (student_formation_id) REFERENCES formation (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D93D64910405986 ON user (institution_id)');
        $this->addSql('CREATE INDEX IDX_8D93D6499D99866C ON user (student_semester_id)');
        $this->addSql('CREATE INDEX IDX_8D93D649CBDE9999 ON user (student_formation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course_institution DROP FOREIGN KEY FK_4EF1A831591CC992');
        $this->addSql('ALTER TABLE course_institution DROP FOREIGN KEY FK_4EF1A83110405986');
        $this->addSql('ALTER TABLE course_semester DROP FOREIGN KEY FK_5E8CE3BB591CC992');
        $this->addSql('ALTER TABLE course_semester DROP FOREIGN KEY FK_5E8CE3BB4A798B6F');
        $this->addSql('ALTER TABLE course_formation DROP FOREIGN KEY FK_C2E16113591CC992');
        $this->addSql('ALTER TABLE course_formation DROP FOREIGN KEY FK_C2E161135200282E');
        $this->addSql('ALTER TABLE course_teacher DROP FOREIGN KEY FK_B835A339591CC992');
        $this->addSql('ALTER TABLE course_teacher DROP FOREIGN KEY FK_B835A339A76ED395');
        $this->addSql('ALTER TABLE formation DROP FOREIGN KEY FK_404021BF10405986');
        $this->addSql('ALTER TABLE semester DROP FOREIGN KEY FK_F7388EED10405986');
        $this->addSql('ALTER TABLE user_institution DROP FOREIGN KEY FK_93845170A76ED395');
        $this->addSql('ALTER TABLE user_institution DROP FOREIGN KEY FK_9384517010405986');
        $this->addSql('DROP TABLE course_institution');
        $this->addSql('DROP TABLE course_semester');
        $this->addSql('DROP TABLE course_formation');
        $this->addSql('DROP TABLE course_teacher');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE institution');
        $this->addSql('DROP TABLE semester');
        $this->addSql('DROP TABLE user_institution');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64910405986');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6499D99866C');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649CBDE9999');
        $this->addSql('DROP INDEX IDX_8D93D64910405986 ON `user`');
        $this->addSql('DROP INDEX IDX_8D93D6499D99866C ON `user`');
        $this->addSql('DROP INDEX IDX_8D93D649CBDE9999 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP institution_id, DROP student_semester_id, DROP student_formation_id');
    }
}
