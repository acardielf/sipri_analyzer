<?php

namespace App\Enum;

enum TipoPlazaEnum: string
{
    case SUSTITUCION = 'SUSTITUCION';
    case VACANTE = 'VACANTE';

    public static function fromString(string $value): self
    {
        return match ($value) {
            'SUSTITUCION', "Sustitución" => self::SUSTITUCION,
            'VACANTE', "Vacante" => self::VACANTE,
            default => throw new \InvalidArgumentException("Invalid value for TipoPlazaEnum: $value"),
        };
    }

}
