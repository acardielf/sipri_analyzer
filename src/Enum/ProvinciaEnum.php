<?php

namespace App\Enum;

enum ProvinciaEnum: string
{
    case ALMERIA = 'ALMERÍA';
    case CADIZ = 'CADIZ';
    case CORDOBA = 'CORDOBA';
    case GRANADA = 'GRANADA';
    case HUELVA = 'HUELVA';
    case JAEN = 'JAEN';
    case MALAGA = 'MALAGA';
    case SEVILLA = 'SEVILLA';


    public static function fromStringWithCode(string $provincia): self
    {
        return match ($provincia) {
            '4 - Almería', 'ALMERÍA', 'AL' => self::ALMERIA,
            '11 - Cádiz', 'CADIZ', 'CA' => self::CADIZ,
            '14 - Córdoba', 'CORDOBA', 'CO' => self::CORDOBA,
            '18 - Granada', 'GRANADA', 'GR' => self::GRANADA,
            '21 - Huelva', 'HUELVA', 'H' => self::HUELVA,
            '23 - Jaén', 'JAEN', 'J' => self::JAEN,
            '29 - Málaga', 'MALAGA', 'MA' => self::MALAGA,
            '41 - Sevilla', 'SEVILLA', 'SE' => self::SEVILLA,
            default => throw new \InvalidArgumentException("Invalid value for ProvinciaEnum: $provincia"),
        };
    }

    public function getWithCode(): string
    {
        return match ($this) {
            self::ALMERIA => '4 - Almería',
            self::CADIZ => '11 - Cádiz',
            self::CORDOBA => '14 - Córdoba',
            self::GRANADA => '18 - Granada',
            self::HUELVA => '21 - Huelva',
            self::JAEN => '23 - Jaén',
            self::MALAGA => '29 - Málaga',
            self::SEVILLA => '41 - Sevilla',
        };
    }

}
