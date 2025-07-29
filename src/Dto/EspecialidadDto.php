<?php

namespace App\Dto;

readonly class EspecialidadDto
{
    public function __construct(
        public string $id,
        public string $nombre,
        public CuerpoDto $cuerpo,
        public ?string $codigo = null,
    ) {
    }

    public static function fromString(string $especialidad): self
    {
        $values = explode(' - ', $especialidad, 2);
        return new self(
            id: $values[0] ?? '',
            nombre: $values[1] ?? '',
            cuerpo: CuerpoDto::fromEspecialidadString($values[0]),
            codigo: null,
        );
    }
}
