<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221117134414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document ADD dossier_agrement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A762D955D9B FOREIGN KEY (dossier_agrement_id) REFERENCES dossier_agrement (id)');
        $this->addSql('CREATE INDEX IDX_D8698A762D955D9B ON document (dossier_agrement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A762D955D9B');
        $this->addSql('DROP INDEX IDX_D8698A762D955D9B ON document');
        $this->addSql('ALTER TABLE document DROP dossier_agrement_id');
    }
}
