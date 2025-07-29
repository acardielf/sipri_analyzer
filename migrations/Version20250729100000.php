<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250729100000 extends AbstractMigration
{
    protected static array $body = [
        '511' => 'Cuerpo de catedráticos de Enseñanza Secundaria',
        '512' => 'Cuerpo de catedráticos de Escuelas Oficiales de Idiomas',
        '513' => 'Cuerpo de catedráticos de Artes Plásticas y Diseño',
        '590' => 'Cuerpo de profesores de Enseñanza Secundaria',
        '591' => 'Cuerpo de profesores Técnicos de Formación Profesional',
        '592' => 'Cuerpo de profesores de Escuelas Oficiales de Idiomas',
        '593' => 'Cuerpo de catedráticos de Música y Artes Escénicas',
        '594' => 'Cuerpo de profesores de Música y Artes Escénicas',
        '595' => 'Cuerpo de profesores de Artes Plásticas y Diseño',
        '596' => 'Cuerpo de Maestros de Taller de Artes Plásticas y Diseño',
        '597' => 'Cuerpo de Maestros',
        '999' => 'Otras especialidades / Puestos específicos',
    ];


    public function getDescription(): string
    {
        return 'Cuerpos docentes';
    }

    public function up(Schema $schema): void
    {
        foreach (self::$body as $key => $value) {
            $this->addSql("INSERT INTO cuerpo (id, nombre) VALUES ('$key', '$value')");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cuerpo');
    }
}
