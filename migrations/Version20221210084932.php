<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221210084932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_agrement ADD utilisateur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE dossier_agrement ADD CONSTRAINT FK_234C6EBEFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_234C6EBEFB88E14F ON dossier_agrement (utilisateur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_agrement DROP FOREIGN KEY FK_234C6EBEFB88E14F');
        $this->addSql('DROP INDEX IDX_234C6EBEFB88E14F ON dossier_agrement');
        $this->addSql('ALTER TABLE dossier_agrement DROP utilisateur_id');
    }
}
