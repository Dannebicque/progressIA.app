<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260807111617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ai_usage_log (id INT AUTO_INCREMENT NOT NULL, feature VARCHAR(100) NOT NULL, prompt_tokens INT NOT NULL, completion_tokens INT NOT NULL, estimated_cost NUMERIC(10, 5) DEFAULT \'0.00000\' NOT NULL, created_at DATETIME NOT NULL, institution_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_B9BDAD3410405986 (institution_id), INDEX IDX_B9BDAD34A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE landing_config (id INT AUTO_INCREMENT NOT NULL, hero_title VARCHAR(255) NOT NULL, hero_subtitle VARCHAR(500) NOT NULL, plans_json JSON NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ai_usage_log ADD CONSTRAINT FK_B9BDAD3410405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ai_usage_log ADD CONSTRAINT FK_B9BDAD34A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE institution ADD ai_enabled TINYINT DEFAULT 0 NOT NULL, ADD ai_config_type VARCHAR(20) DEFAULT \'global\' NOT NULL, ADD ai_provider VARCHAR(30) DEFAULT \'groq\' NOT NULL, ADD ai_model VARCHAR(100) DEFAULT \'llama-3.1-70b-versatile\' NOT NULL, ADD ai_api_key VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_usage_log DROP FOREIGN KEY FK_B9BDAD3410405986');
        $this->addSql('ALTER TABLE ai_usage_log DROP FOREIGN KEY FK_B9BDAD34A76ED395');
        $this->addSql('DROP TABLE ai_usage_log');
        $this->addSql('DROP TABLE landing_config');
        $this->addSql('ALTER TABLE institution DROP ai_enabled, DROP ai_config_type, DROP ai_provider, DROP ai_model, DROP ai_api_key');
    }
}
