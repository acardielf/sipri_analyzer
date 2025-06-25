<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250625105502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__convocatoria AS SELECT id, curso_id, nombre, fecha FROM convocatoria
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE convocatoria
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE convocatoria (id INTEGER NOT NULL, curso_id INTEGER DEFAULT NULL, nombre VARCHAR(12) NOT NULL, fecha DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
            , PRIMARY KEY(id), CONSTRAINT FK_6D77302187CB4A1F FOREIGN KEY (curso_id) REFERENCES curso (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO convocatoria (id, curso_id, nombre, fecha) SELECT id, curso_id, nombre, fecha FROM __temp__convocatoria
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__convocatoria
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6D77302187CB4A1F ON convocatoria (curso_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__curso AS SELECT id, nombre, simple FROM curso
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE curso
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE curso (id INTEGER NOT NULL, nombre VARCHAR(9) NOT NULL, simple VARCHAR(4) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO curso (id, nombre, simple) SELECT id, nombre, simple FROM __temp__curso
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__curso
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__provincia AS SELECT id, nombre FROM provincia
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE provincia
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE provincia (id INTEGER NOT NULL, nombre VARCHAR(7) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO provincia (id, nombre) SELECT id, nombre FROM __temp__provincia
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__provincia
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__convocatoria AS SELECT id, curso_id, nombre, fecha FROM convocatoria
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE convocatoria
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE convocatoria (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, curso_id INTEGER DEFAULT NULL, nombre VARCHAR(12) NOT NULL, fecha DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
            , CONSTRAINT FK_6D77302187CB4A1F FOREIGN KEY (curso_id) REFERENCES curso (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO convocatoria (id, curso_id, nombre, fecha) SELECT id, curso_id, nombre, fecha FROM __temp__convocatoria
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__convocatoria
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6D77302187CB4A1F ON convocatoria (curso_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__curso AS SELECT id, nombre, simple FROM curso
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE curso
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE curso (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nombre VARCHAR(9) NOT NULL, simple VARCHAR(4) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO curso (id, nombre, simple) SELECT id, nombre, simple FROM __temp__curso
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__curso
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__provincia AS SELECT id, nombre FROM provincia
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE provincia
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE provincia (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nombre VARCHAR(7) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO provincia (id, nombre) SELECT id, nombre FROM __temp__provincia
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__provincia
        SQL);
    }
}
