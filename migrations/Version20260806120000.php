<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Siembra el curso 2026/2027 para que la columna exista antes de que se
 * publique su primera convocatoria (1 de septiembre de 2026).
 */
final class Version20260806120000 extends AbstractMigration
{
    protected static array $cursos = [
        '2026' => ['nombre' => '2026/2027', 'simple' => '2026'],
    ];

    public function getDescription(): string
    {
        return 'Curso 2026/2027';
    }

    public function up(Schema $schema): void
    {
        foreach (self::$cursos as $id => $curso) {
            $this->addSql(
                "INSERT OR IGNORE INTO curso (id, nombre, simple) VALUES ('$id', '{$curso['nombre']}', '{$curso['simple']}')"
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::$cursos) as $id) {
            $this->addSql("DELETE FROM curso WHERE id = '$id'");
        }
    }
}
