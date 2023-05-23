<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221113085306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_agrement ADD nb_salarie INT DEFAULT NULL, ADD montant VARCHAR(255) DEFAULT NULL, ADD type_cotisation VARCHAR(255) DEFAULT NULL, ADD frais_de_dossier VARCHAR(255) DEFAULT NULL, ADD compte_numerique_bool TINYINT(1) DEFAULT NULL, ADD compte_numerique VARCHAR(255) DEFAULT NULL, ADD terminal_paiement_bool TINYINT(1) DEFAULT NULL, ADD terminal_paiement VARCHAR(255) DEFAULT NULL, ADD euskopay_bool TINYINT(1) DEFAULT NULL, ADD euskopay VARCHAR(255) DEFAULT NULL, ADD paiement_via_euskopay TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_agrement DROP nb_salarie, DROP montant, DROP type_cotisation, DROP frais_de_dossier, DROP compte_numerique_bool, DROP compte_numerique, DROP terminal_paiement_bool, DROP terminal_paiement, DROP euskopay_bool, DROP euskopay, DROP paiement_via_euskopay');
    }
}
