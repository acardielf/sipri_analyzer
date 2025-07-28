<?php

namespace App\Dto;

use App\Entity\Plaza;
use App\Enum\ObligatoriedadPlazaEnum;
use App\Enum\TipoPlazaEnum;

readonly class PlazaDto
{
    public function __construct(
        public ?int $id,
        public ConvocatoriaDto $convocatoria,
        public CentroDto $centro,
        public EspecialidadDto $especialidad,
        public TipoPlazaEnum $tipoPlaza,
        public ObligatoriedadPlazaEnum $obligatoriedadPlaza,
        public ?\DateTimeImmutable $fechaPrevistaCese,
        public int $numero,
        public int $pagina,
        public int $fila,
    ) {
    }

    public function getHash(?int $ocurrencia): string
    {
        return Plaza::buildHash(
            convocatoriaId: $this->convocatoria->id,
            centroId: $this->centro->id,
            especialidadId: $this->especialidad->id,
            tipoPlaza: $this->tipoPlaza,
            obligatoriedadPlaza: $this->obligatoriedadPlaza,
            fechaPrevistaCese: $this->fechaPrevistaCese,
            numero: $this->numero,
            ocurrencia: $ocurrencia,
        );
    }


}
