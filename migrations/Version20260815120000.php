<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Da de alta el cuerpo 598 y reasigna las especialidades que hasta ahora
 * caían en el cajón de sastre 999 por no existir la fila del cuerpo.
 *
 * No hace falta reprocesar los PDFs: CuerpoDto::fromEspecialidadString()
 * deriva el cuerpo con substr($id, 2, 3), así que a partir de ahora las
 * especialidades 598 nuevas se enlazan solas.
 */
final class Version20260815120000 extends AbstractMigration
{
    private const string CUERPO_ID = '598';

    private const string CUERPO_NOMBRE = 'Cuerpo de profesores Especialistas en Sectores Singulares';

    public function getDescription(): string
    {
        return 'Cuerpo 598 (Especialistas en Sectores Singulares de FP)';
    }

    public function up(Schema $schema): void
    {
        $id = self::CUERPO_ID;
        $nombre = self::CUERPO_NOMBRE;

        $this->addSql("INSERT OR IGNORE INTO cuerpo (id, nombre) VALUES ('$id', '$nombre')");
        $this->addSql(
            "UPDATE especialidad SET cuerpo_id = '$id' WHERE substr(id, 3, 3) = '$id' AND cuerpo_id = '999'"
        );
    }

    public function down(Schema $schema): void
    {
        $id = self::CUERPO_ID;

        $this->addSql("UPDATE especialidad SET cuerpo_id = '999' WHERE cuerpo_id = '$id'");
        $this->addSql("DELETE FROM cuerpo WHERE id = '$id'");
    }
}
