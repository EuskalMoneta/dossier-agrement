<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221104082534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_agrement ADD email_principal VARCHAR(255) DEFAULT NULL, ADD nom_dirigeant VARCHAR(255) DEFAULT NULL, ADD prenom_dirigeant VARCHAR(255) DEFAULT NULL, ADD telephone_dirigeant VARCHAR(255) DEFAULT NULL, ADD email_dirigeant VARCHAR(255) DEFAULT NULL, ADD fonction_dirigeant VARCHAR(255) DEFAULT NULL, ADD interlocuteur_dirigeant TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_agrement DROP email_principal, DROP nom_dirigeant, DROP prenom_dirigeant, DROP telephone_dirigeant, DROP email_dirigeant, DROP fonction_dirigeant, DROP interlocuteur_dirigeant');
    }
}
