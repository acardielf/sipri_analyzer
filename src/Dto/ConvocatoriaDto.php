<?php

namespace App\Dto;

use App\Entity\Convocatoria;
use DateTimeImmutable;
use Exception;

readonly class ConvocatoriaDto
{
    public function __construct(
        public string             $id,
        public string             $nombre,
        public ?DateTimeImmutable $fecha,
        public CursoDto           $curso,
    )
    {
    }

    /**
     * @throws Exception
     */
    public static function fromId(int $convocatoria, ?DateTimeImmutable $fecha): ConvocatoriaDto
    {
        return new ConvocatoriaDto(
            id: (string)$convocatoria,
            nombre: str_pad((string)$convocatoria, 12, '0', STR_PAD_LEFT),
            fecha: $fecha,
            curso: Convocatoria::getCursoFromConvocatoria($convocatoria),
        );
    }


}
