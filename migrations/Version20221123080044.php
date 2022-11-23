<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221123080044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_agrement ADD iban VARCHAR(255) DEFAULT NULL, ADD bic VARCHAR(255) DEFAULT NULL, ADD nom_signature VARCHAR(255) DEFAULT NULL, ADD prenom_signature VARCHAR(255) DEFAULT NULL, ADD telephone_signature VARCHAR(255) DEFAULT NULL, ADD frais_de_dossier_recu VARCHAR(255) DEFAULT NULL, CHANGE sepa siren VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_agrement ADD sepa VARCHAR(255) DEFAULT NULL, DROP siren, DROP iban, DROP bic, DROP nom_signature, DROP prenom_signature, DROP telephone_signature, DROP frais_de_dossier_recu');
    }
}
