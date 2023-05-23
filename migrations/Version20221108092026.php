<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221108092026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dossier_agrement_reduction_adhesion (dossier_agrement_id INT NOT NULL, reduction_adhesion_id INT NOT NULL, INDEX IDX_59A9B9162D955D9B (dossier_agrement_id), INDEX IDX_59A9B916C424FDAF (reduction_adhesion_id), PRIMARY KEY(dossier_agrement_id, reduction_adhesion_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reduction_adhesion (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) DEFAULT NULL, pourcentage_reduction DOUBLE PRECISION DEFAULT NULL, visible TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dossier_agrement_reduction_adhesion ADD CONSTRAINT FK_59A9B9162D955D9B FOREIGN KEY (dossier_agrement_id) REFERENCES dossier_agrement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dossier_agrement_reduction_adhesion ADD CONSTRAINT FK_59A9B916C424FDAF FOREIGN KEY (reduction_adhesion_id) REFERENCES reduction_adhesion (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_agrement_reduction_adhesion DROP FOREIGN KEY FK_59A9B9162D955D9B');
        $this->addSql('ALTER TABLE dossier_agrement_reduction_adhesion DROP FOREIGN KEY FK_59A9B916C424FDAF');
        $this->addSql('DROP TABLE dossier_agrement_reduction_adhesion');
        $this->addSql('DROP TABLE reduction_adhesion');
    }
}
