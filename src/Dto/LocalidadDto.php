<?php

namespace App\Dto;

readonly class LocalidadDto
{

    public function __construct(
        public string       $id,
        public string       $nombre,
        public ProvinciaDto $provincia,
    )
    {
    }

    public static function fromString(string $localidad, string $provincia): self
    {
        $values = explode(' - ', $localidad, 2);
        return new self(
            id: intval($values[0]) ?? '',
            nombre: $values[1] ?? '',
            provincia: ProvinciaDto::fromString($provincia),
        );
    }

}
