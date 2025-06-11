<?php

namespace App\Dto;

readonly class ConvocatoriaDto
{
    public function __construct(
        public string   $id,
        public string   $nombre,
        public CursoDto $curso,
    )
    {
    }

    public static function fromId(int $convocatoria): ConvocatoriaDto
    {
        return new ConvocatoriaDto(
            id: (string)$convocatoria,
            nombre: str_pad((string)$convocatoria, 12, '0', STR_PAD_LEFT),
            curso: CursoDto::fromYear($convocatoria),
        );
    }
}
