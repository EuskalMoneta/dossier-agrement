<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221102075658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact ADD dossier_agrement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E6382D955D9B FOREIGN KEY (dossier_agrement_id) REFERENCES dossier_agrement (id)');
        $this->addSql('CREATE INDEX IDX_4C62E6382D955D9B ON contact (dossier_agrement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E6382D955D9B');
        $this->addSql('DROP INDEX IDX_4C62E6382D955D9B ON contact');
        $this->addSql('ALTER TABLE contact DROP dossier_agrement_id');
    }
}
