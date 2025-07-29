<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250729004756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE adjudicacion (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, plaza_id INTEGER DEFAULT NULL, orden INTEGER NOT NULL, CONSTRAINT FK_1299069AEF34C0BD FOREIGN KEY (plaza_id) REFERENCES plaza (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1299069AEF34C0BD ON adjudicacion (plaza_id)');
        $this->addSql('CREATE TABLE centro (id VARCHAR(255) NOT NULL, localidad_id INTEGER DEFAULT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_2675036B67707C89 FOREIGN KEY (localidad_id) REFERENCES localidad (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2675036B67707C89 ON centro (localidad_id)');
        $this->addSql('CREATE TABLE convocatoria (id VARCHAR(255) NOT NULL, curso_id VARCHAR(255) DEFAULT NULL, nombre VARCHAR(12) NOT NULL, fecha DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_6D77302187CB4A1F FOREIGN KEY (curso_id) REFERENCES curso (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6D77302187CB4A1F ON convocatoria (curso_id)');
        $this->addSql('CREATE TABLE cuerpo (id VARCHAR(255) NOT NULL, nombre VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE curso (id VARCHAR(255) NOT NULL, nombre VARCHAR(9) NOT NULL, simple VARCHAR(4) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE especialidad (id VARCHAR(255) NOT NULL, cuerpo_id VARCHAR(255) DEFAULT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_ACB064F9FBA4E20B FOREIGN KEY (cuerpo_id) REFERENCES cuerpo (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_ACB064F9FBA4E20B ON especialidad (cuerpo_id)');
        $this->addSql('CREATE TABLE localidad (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, provincia_id VARCHAR(255) DEFAULT NULL, nombre VARCHAR(255) NOT NULL, CONSTRAINT FK_4F68E0104E7121AF FOREIGN KEY (provincia_id) REFERENCES provincia (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4F68E0104E7121AF ON localidad (provincia_id)');
        $this->addSql('CREATE TABLE plaza (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, convocatoria_id VARCHAR(255) DEFAULT NULL, centro_id VARCHAR(255) DEFAULT NULL, especialidad_id VARCHAR(255) DEFAULT NULL, tipo VARCHAR(255) NOT NULL, obligatoriedad VARCHAR(255) NOT NULL, fecha_prevista_cese DATE DEFAULT NULL --(DC2Type:date_immutable)
        , numero INTEGER NOT NULL, ocurrencia INTEGER NOT NULL, pagina INTEGER DEFAULT NULL, linea INTEGER DEFAULT NULL, hash VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_E8703ECC4EE93BE6 FOREIGN KEY (convocatoria_id) REFERENCES convocatoria (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E8703ECC298137A7 FOREIGN KEY (centro_id) REFERENCES centro (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E8703ECC16A490EC FOREIGN KEY (especialidad_id) REFERENCES especialidad (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E8703ECC4EE93BE6 ON plaza (convocatoria_id)');
        $this->addSql('CREATE INDEX IDX_E8703ECC298137A7 ON plaza (centro_id)');
        $this->addSql('CREATE INDEX IDX_E8703ECC16A490EC ON plaza (especialidad_id)');
        $this->addSql('CREATE TABLE provincia (id VARCHAR(255) NOT NULL, nombre VARCHAR(7) NOT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE adjudicacion');
        $this->addSql('DROP TABLE centro');
        $this->addSql('DROP TABLE convocatoria');
        $this->addSql('DROP TABLE cuerpo');
        $this->addSql('DROP TABLE curso');
        $this->addSql('DROP TABLE especialidad');
        $this->addSql('DROP TABLE localidad');
        $this->addSql('DROP TABLE plaza');
        $this->addSql('DROP TABLE provincia');
    }
}
