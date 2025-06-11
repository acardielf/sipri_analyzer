<?php

namespace App\Dto;

readonly class EspecialidadDto
{
    public function __construct(
        public string $id,
        public string $nombre,
        public ?string $codigo = null,
    ) {
    }

    public static function fromString(string $especialidad): self
    {
        $values = explode(' - ', $especialidad, 2);
        return new self(
            id: $values[0] ?? '',
            nombre: $values[1] ?? '',
            codigo: null,
        );
    }
}
