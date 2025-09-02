<?php

namespace App\Entity;

use App\Dto\CursoDto;
use Exception;
use http\Exception\RuntimeException;

trait ConvocatoriaConfigurationTrait
{

    private const array CONVOCATORIA_RANGES = [
        ['min' => 1, 'max' => 30, 'year' => 2018], // 29 convocatorias
        ['min' => 31, 'max' => 81, 'year' => 2019], // 51 convocatorias
        ['min' => 82, 'max' => 136, 'year' => 2020], // 55 convocatorias
        ['min' => 137, 'max' => 198, 'year' => 2021], // 62 convocatorias
        ['min' => 200, 'max' => 262, 'year' => 2022], // 63 convocatorias
        ['min' => 263, 'max' => 324, 'year' => 2023], // 62 convocatorias
        ['min' => 325, 'max' => 388, 'year' => 2024], // 64 convocatorias
        ['min' => 389, 'max' => 459, 'year' => 2025], // suposición de maximo 70 convocatorias. max = 389+70 = 459
    ];
    private const array CONVOCATORIA_AUSENTE = [
        26, 45, 91, 114, 155, 180, 199, 260, 265,
        73, // Convocatoria 73 no se publicó por COVID-19
    ];

    /**
     * @throws RuntimeException
     */
    public static function getCursoFromConvocatoria(int $convocatoria): CursoDto
    {
        if (in_array($convocatoria, self::CONVOCATORIA_AUSENTE, true)) {
            throw new RuntimeException(
                sprintf('No existe ninguna convocatoria convocatoria con el número %d', $convocatoria)
            );
        }

        foreach (self::CONVOCATORIA_RANGES as $range) {
            if ($convocatoria >= $range['min'] && $convocatoria <= $range['max']) {
                return CursoDto::fromYear($range['year']);
            }
        }

        throw new RuntimeException(
            sprintf('No se encontró curso para la convocatoria %d', $convocatoria)
        );
    }

}
