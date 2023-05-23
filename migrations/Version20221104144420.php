<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221104144420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE adresse_activite_categorie_annuaire (adresse_activite_id INT NOT NULL, categorie_annuaire_id INT NOT NULL, INDEX IDX_37BF989A5501715E (adresse_activite_id), INDEX IDX_37BF989A8ECC9E63 (categorie_annuaire_id), PRIMARY KEY(adresse_activite_id, categorie_annuaire_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE adresse_activite_categorie_annuaire ADD CONSTRAINT FK_37BF989A5501715E FOREIGN KEY (adresse_activite_id) REFERENCES adresse_activite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE adresse_activite_categorie_annuaire ADD CONSTRAINT FK_37BF989A8ECC9E63 FOREIGN KEY (categorie_annuaire_id) REFERENCES categorie_annuaire (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE adresse_activite_categorie_annuaire DROP FOREIGN KEY FK_37BF989A5501715E');
        $this->addSql('ALTER TABLE adresse_activite_categorie_annuaire DROP FOREIGN KEY FK_37BF989A8ECC9E63');
        $this->addSql('DROP TABLE adresse_activite_categorie_annuaire');
    }
}
