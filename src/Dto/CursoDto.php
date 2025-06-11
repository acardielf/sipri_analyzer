<?php

namespace App\Dto;

readonly class CursoDto
{
    public function __construct(
        public string $id,
        public string $nombre,
        public string $simple,
    )
    {
    }

    public static function fromYear(int $year): self
    {
        return new self(
            id: (string)$year,
            nombre: sprintf('%d/%d', $year, $year + 1),
            simple: (string)$year,
        );
    }
}
