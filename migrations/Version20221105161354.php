<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221105161354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE defi (id INT AUTO_INCREMENT NOT NULL, dossier_agrement_id INT DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, valeur LONGTEXT DEFAULT NULL, etat TINYINT(1) DEFAULT NULL, INDEX IDX_DCD5A35E2D955D9B (dossier_agrement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE defi ADD CONSTRAINT FK_DCD5A35E2D955D9B FOREIGN KEY (dossier_agrement_id) REFERENCES dossier_agrement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE defi DROP FOREIGN KEY FK_DCD5A35E2D955D9B');
        $this->addSql('DROP TABLE defi');
    }
}
