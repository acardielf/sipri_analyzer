<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250728144814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix cascade';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__convocatoria AS SELECT id, curso_id, nombre, fecha FROM convocatoria');
        $this->addSql('DROP TABLE convocatoria');
        $this->addSql('CREATE TABLE convocatoria (id INTEGER NOT NULL, curso_id INTEGER DEFAULT NULL, nombre VARCHAR(12) NOT NULL, fecha DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_6D77302187CB4A1F FOREIGN KEY (curso_id) REFERENCES curso (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO convocatoria (id, curso_id, nombre, fecha) SELECT id, curso_id, nombre, fecha FROM __temp__convocatoria');
        $this->addSql('DROP TABLE __temp__convocatoria');
        $this->addSql('CREATE INDEX IDX_6D77302187CB4A1F ON convocatoria (curso_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__curso AS SELECT id, nombre, simple FROM curso');
        $this->addSql('DROP TABLE curso');
        $this->addSql('CREATE TABLE curso (id INTEGER NOT NULL, nombre VARCHAR(9) NOT NULL, simple VARCHAR(4) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO curso (id, nombre, simple) SELECT id, nombre, simple FROM __temp__curso');
        $this->addSql('DROP TABLE __temp__curso');
        $this->addSql('CREATE TEMPORARY TABLE __temp__provincia AS SELECT id, nombre FROM provincia');
        $this->addSql('DROP TABLE provincia');
        $this->addSql('CREATE TABLE provincia (id INTEGER NOT NULL, nombre VARCHAR(7) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO provincia (id, nombre) SELECT id, nombre FROM __temp__provincia');
        $this->addSql('DROP TABLE __temp__provincia');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__convocatoria AS SELECT id, curso_id, nombre, fecha FROM convocatoria');
        $this->addSql('DROP TABLE convocatoria');
        $this->addSql('CREATE TABLE convocatoria (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, curso_id INTEGER DEFAULT NULL, nombre VARCHAR(12) NOT NULL, fecha DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_6D77302187CB4A1F FOREIGN KEY (curso_id) REFERENCES curso (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO convocatoria (id, curso_id, nombre, fecha) SELECT id, curso_id, nombre, fecha FROM __temp__convocatoria');
        $this->addSql('DROP TABLE __temp__convocatoria');
        $this->addSql('CREATE INDEX IDX_6D77302187CB4A1F ON convocatoria (curso_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__curso AS SELECT id, nombre, simple FROM curso');
        $this->addSql('DROP TABLE curso');
        $this->addSql('CREATE TABLE curso (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nombre VARCHAR(9) NOT NULL, simple VARCHAR(4) NOT NULL)');
        $this->addSql('INSERT INTO curso (id, nombre, simple) SELECT id, nombre, simple FROM __temp__curso');
        $this->addSql('DROP TABLE __temp__curso');
        $this->addSql('CREATE TEMPORARY TABLE __temp__provincia AS SELECT id, nombre FROM provincia');
        $this->addSql('DROP TABLE provincia');
        $this->addSql('CREATE TABLE provincia (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nombre VARCHAR(7) NOT NULL)');
        $this->addSql('INSERT INTO provincia (id, nombre) SELECT id, nombre FROM __temp__provincia');
        $this->addSql('DROP TABLE __temp__provincia');
    }
}
