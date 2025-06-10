<?php

namespace App\Enum;

enum ObligatoriedadPlazaEnum: string
{
    case VOL = 'VOLUNTARIA';
    case OBL = 'OBLIGATORIA';
    case SN = 'SÍ/NO';

    public static function fromString(mixed $obligatoriedad)
    {
        if (is_string($obligatoriedad)) {
            $obligatoriedad = mb_strtoupper($obligatoriedad);
        }

        return match ($obligatoriedad) {
            'VOLUNTARIA', 'VOL', 'N' => self::VOL,
            'OBLIGATORIA', 'OBL', 'S' => self::OBL,
            'SÍ/NO', 'SN', 'S/N' => self::SN,
            default => throw new \InvalidArgumentException("Invalid value for ObligatoriedadPlazaEnum: $obligatoriedad"),
        };
    }
}
