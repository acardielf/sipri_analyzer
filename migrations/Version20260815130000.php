<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Amplía cuerpo.nombre de VARCHAR(50) a VARCHAR(60): varios nombres ya
 * superaban los 50 caracteres (el 596 tiene 56, el 598 nuevo tiene 56).
 * SQLite no aplica el límite, pero el mapeo debe reflejar la realidad.
 *
 * SQLite no soporta ALTER COLUMN, así que se recrea la tabla.
 */
final class Version20260815130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'cuerpo.nombre pasa a VARCHAR(60)';
    }

    public function up(Schema $schema): void
    {
        $this->recreate(60);
    }

    public function down(Schema $schema): void
    {
        $this->recreate(50);
    }

    private function recreate(int $length): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__cuerpo AS SELECT id, nombre FROM cuerpo');
        $this->addSql('DROP TABLE cuerpo');
        $this->addSql(
            "CREATE TABLE cuerpo (id VARCHAR(255) NOT NULL, nombre VARCHAR($length) NOT NULL, PRIMARY KEY(id))"
        );
        $this->addSql('INSERT INTO cuerpo (id, nombre) SELECT id, nombre FROM __temp__cuerpo');
        $this->addSql('DROP TABLE __temp__cuerpo');
    }
}
