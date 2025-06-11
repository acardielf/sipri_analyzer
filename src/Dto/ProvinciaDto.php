<?php

namespace App\Dto;

readonly class ProvinciaDto
{

    public function __construct(
        public string $id,
        public string $nombre,
    )
    {
    }

    public static function fromString(string $provincia): self
    {
        $values = explode(' - ', $provincia, 2);

        return new self(
            id: $values[0] ?? '',
            nombre: $values[1] ?? '',
        );
    }
}
