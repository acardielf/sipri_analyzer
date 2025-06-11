<?php

namespace App\Dto;

use App\Enum\ObligatoriedadPlazaEnum;
use App\Enum\TipoPlazaEnum;

readonly class PlazaDto
{
    public function __construct(
        public ?int                    $id,
        public ConvocatoriaDto         $convocatoria,
        public CentroDto               $centro,
        public EspecialidadDto         $especialidad,
        public TipoPlazaEnum           $tipoPlaza,
        public ObligatoriedadPlazaEnum $obligatoriedadPlaza,
        public ?\DateTimeImmutable     $fechaPrevistaCese,
        public int                     $numero,
    )
    {
    }

}
