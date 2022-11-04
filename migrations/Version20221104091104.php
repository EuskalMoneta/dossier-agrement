<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221104091104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE adresse_activite (id INT AUTO_INCREMENT NOT NULL, dossier_id INT DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, adresse LONGTEXT DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, horaires LONGTEXT DEFAULT NULL, facebook VARCHAR(255) DEFAULT NULL, instagram VARCHAR(255) DEFAULT NULL, descriptif_activite LONGTEXT DEFAULT NULL, autres_lieux LONGTEXT DEFAULT NULL, categorie_annuaire VARCHAR(255) DEFAULT NULL, guide_vee TINYINT(1) DEFAULT NULL, INDEX IDX_D86BC57F611C0C56 (dossier_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE adresse_activite ADD CONSTRAINT FK_D86BC57F611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier_agrement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE adresse_activite DROP FOREIGN KEY FK_D86BC57F611C0C56');
        $this->addSql('DROP TABLE adresse_activite');
    }
}
