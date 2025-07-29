<?php

namespace App\Dto;

readonly class CuerpoDto
{

    public function __construct(
        public string $id,
        public string $nombre,
    ) {
    }

    public static function fromEspecialidadString(string $especialidad): self
    {
        $threeChars = substr($especialidad, 2, 3);

        return new self(
            id: $threeChars,
            nombre: '',
        );
    }

}
