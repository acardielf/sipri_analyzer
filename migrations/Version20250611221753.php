<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250611221753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE centro (id VARCHAR(255) NOT NULL, localidad_id INTEGER DEFAULT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_2675036B67707C89 FOREIGN KEY (localidad_id) REFERENCES localidad (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2675036B67707C89 ON centro (localidad_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE convocatoria (id INTEGER NOT NULL, curso_id INTEGER DEFAULT NULL, nombre VARCHAR(12) NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_6D77302187CB4A1F FOREIGN KEY (curso_id) REFERENCES curso (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6D77302187CB4A1F ON convocatoria (curso_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE curso (id INTEGER NOT NULL, nombre VARCHAR(9) NOT NULL, simple VARCHAR(4) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE especialidad (id VARCHAR(255) NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE localidad (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, provincia_id INTEGER DEFAULT NULL, nombre VARCHAR(255) NOT NULL, CONSTRAINT FK_4F68E0104E7121AF FOREIGN KEY (provincia_id) REFERENCES provincia (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4F68E0104E7121AF ON localidad (provincia_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE plaza (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, convocatoria_id INTEGER DEFAULT NULL, centro_id VARCHAR(255) DEFAULT NULL, especialidad_id VARCHAR(255) DEFAULT NULL, tipo VARCHAR(255) NOT NULL, obligatoriedad VARCHAR(255) NOT NULL, fecha_prevista_cese DATE DEFAULT NULL --(DC2Type:date_immutable)
            , numero INTEGER NOT NULL, ocurrencia INTEGER NOT NULL, hash VARCHAR(255) NOT NULL, CONSTRAINT FK_E8703ECC4EE93BE6 FOREIGN KEY (convocatoria_id) REFERENCES convocatoria (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E8703ECC298137A7 FOREIGN KEY (centro_id) REFERENCES centro (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E8703ECC16A490EC FOREIGN KEY (especialidad_id) REFERENCES especialidad (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E8703ECC4EE93BE6 ON plaza (convocatoria_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E8703ECC298137A7 ON plaza (centro_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E8703ECC16A490EC ON plaza (especialidad_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE provincia (id INTEGER NOT NULL, nombre VARCHAR(7) NOT NULL, PRIMARY KEY(id))
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE centro
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE convocatoria
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE curso
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE especialidad
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE localidad
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE plaza
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE provincia
        SQL);
    }
}
