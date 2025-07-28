<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250728142517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pagina and linea to plaza table';
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
        $this->addSql('CREATE TEMPORARY TABLE __temp__plaza AS SELECT id, convocatoria_id, centro_id, especialidad_id, tipo, obligatoriedad, fecha_prevista_cese, numero, ocurrencia, hash FROM plaza');
        $this->addSql('DROP TABLE plaza');
        $this->addSql('CREATE TABLE plaza (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, convocatoria_id INTEGER DEFAULT NULL, centro_id VARCHAR(255) DEFAULT NULL, especialidad_id VARCHAR(255) DEFAULT NULL, tipo VARCHAR(255) NOT NULL, obligatoriedad VARCHAR(255) NOT NULL, fecha_prevista_cese DATE DEFAULT NULL --(DC2Type:date_immutable)
        , numero INTEGER NOT NULL, ocurrencia INTEGER NOT NULL, hash VARCHAR(255) DEFAULT NULL, pagina INTEGER DEFAULT NULL, linea INTEGER DEFAULT NULL, CONSTRAINT FK_E8703ECC4EE93BE6 FOREIGN KEY (convocatoria_id) REFERENCES convocatoria (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E8703ECC298137A7 FOREIGN KEY (centro_id) REFERENCES centro (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E8703ECC16A490EC FOREIGN KEY (especialidad_id) REFERENCES especialidad (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO plaza (id, convocatoria_id, centro_id, especialidad_id, tipo, obligatoriedad, fecha_prevista_cese, numero, ocurrencia, hash) SELECT id, convocatoria_id, centro_id, especialidad_id, tipo, obligatoriedad, fecha_prevista_cese, numero, ocurrencia, hash FROM __temp__plaza');
        $this->addSql('DROP TABLE __temp__plaza');
        $this->addSql('CREATE INDEX IDX_E8703ECC16A490EC ON plaza (especialidad_id)');
        $this->addSql('CREATE INDEX IDX_E8703ECC298137A7 ON plaza (centro_id)');
        $this->addSql('CREATE INDEX IDX_E8703ECC4EE93BE6 ON plaza (convocatoria_id)');
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
        $this->addSql('CREATE TEMPORARY TABLE __temp__plaza AS SELECT id, convocatoria_id, centro_id, especialidad_id, tipo, obligatoriedad, fecha_prevista_cese, numero, ocurrencia, hash FROM plaza');
        $this->addSql('DROP TABLE plaza');
        $this->addSql('CREATE TABLE plaza (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, convocatoria_id INTEGER DEFAULT NULL, centro_id VARCHAR(255) DEFAULT NULL, especialidad_id VARCHAR(255) DEFAULT NULL, tipo VARCHAR(255) NOT NULL, obligatoriedad VARCHAR(255) NOT NULL, fecha_prevista_cese DATE DEFAULT NULL --(DC2Type:date_immutable)
        , numero INTEGER NOT NULL, ocurrencia INTEGER NOT NULL, hash VARCHAR(255) NOT NULL, CONSTRAINT FK_E8703ECC4EE93BE6 FOREIGN KEY (convocatoria_id) REFERENCES convocatoria (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E8703ECC298137A7 FOREIGN KEY (centro_id) REFERENCES centro (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E8703ECC16A490EC FOREIGN KEY (especialidad_id) REFERENCES especialidad (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO plaza (id, convocatoria_id, centro_id, especialidad_id, tipo, obligatoriedad, fecha_prevista_cese, numero, ocurrencia, hash) SELECT id, convocatoria_id, centro_id, especialidad_id, tipo, obligatoriedad, fecha_prevista_cese, numero, ocurrencia, hash FROM __temp__plaza');
        $this->addSql('DROP TABLE __temp__plaza');
        $this->addSql('CREATE INDEX IDX_E8703ECC4EE93BE6 ON plaza (convocatoria_id)');
        $this->addSql('CREATE INDEX IDX_E8703ECC298137A7 ON plaza (centro_id)');
        $this->addSql('CREATE INDEX IDX_E8703ECC16A490EC ON plaza (especialidad_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__provincia AS SELECT id, nombre FROM provincia');
        $this->addSql('DROP TABLE provincia');
        $this->addSql('CREATE TABLE provincia (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nombre VARCHAR(7) NOT NULL)');
        $this->addSql('INSERT INTO provincia (id, nombre) SELECT id, nombre FROM __temp__provincia');
        $this->addSql('DROP TABLE __temp__provincia');
    }
}
