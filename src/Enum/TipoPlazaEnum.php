<?php

namespace App\Enum;

enum TipoPlazaEnum: string
{
    case SUSTITUCION = 'SUSTITUCION';
    case VACANTE = 'VACANTE';

    public static function fromString(string $value): self
    {
        return match ($value) {
            'S', 'SUSTITUCION', "Sustitución" => self::SUSTITUCION,
            'V', 'VACANTE', "Vacante" => self::VACANTE,
            default => throw new \InvalidArgumentException("Invalid value for TipoPlazaEnum: $value"),
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SUSTITUCION => 'Sustitución',
            self::VACANTE => 'Vacante',
        };
    }

    public function getShortLabel(): string
    {
        return match ($this) {
            self::SUSTITUCION => 'S',
            self::VACANTE => 'V',
        };
    }

}
