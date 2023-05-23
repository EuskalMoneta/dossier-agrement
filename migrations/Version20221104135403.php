<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221104135403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dossier_agrement_categorie_annuaire (dossier_agrement_id INT NOT NULL, categorie_annuaire_id INT NOT NULL, INDEX IDX_EECF3A842D955D9B (dossier_agrement_id), INDEX IDX_EECF3A848ECC9E63 (categorie_annuaire_id), PRIMARY KEY(dossier_agrement_id, categorie_annuaire_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dossier_agrement_categorie_annuaire ADD CONSTRAINT FK_EECF3A842D955D9B FOREIGN KEY (dossier_agrement_id) REFERENCES dossier_agrement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dossier_agrement_categorie_annuaire ADD CONSTRAINT FK_EECF3A848ECC9E63 FOREIGN KEY (categorie_annuaire_id) REFERENCES categorie_annuaire (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_agrement_categorie_annuaire DROP FOREIGN KEY FK_EECF3A842D955D9B');
        $this->addSql('ALTER TABLE dossier_agrement_categorie_annuaire DROP FOREIGN KEY FK_EECF3A848ECC9E63');
        $this->addSql('DROP TABLE dossier_agrement_categorie_annuaire');
    }
}
