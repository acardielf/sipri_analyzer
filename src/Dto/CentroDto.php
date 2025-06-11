<?php

namespace App\Dto;

readonly class CentroDto
{
    public function __construct(
        public string       $id,
        public string       $nombre,
        public LocalidadDto $localidad,
    )
    {
    }

    public static function fromString(string $centro, string $localidad, string $provincia): self
    {
        $values = explode(' - ', $centro, 2);
        return new self(
            id: $values[0] ?? '',
            nombre: $values[1] ?? '',
            localidad: LocalidadDto::fromString($localidad, $provincia),
        );
    }
}
