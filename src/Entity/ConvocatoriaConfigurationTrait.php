<?php

namespace App\Entity;

use App\Dto\CursoDto;
use Exception;

trait ConvocatoriaConfigurationTrait
{

    private const array CONVOCATORIA_RANGES = [
        ['min' => 1, 'max' => 30, 'year' => 2018],
        ['min' => 31, 'max' => 81, 'year' => 2019],
        ['min' => 82, 'max' => 136, 'year' => 2020],
        ['min' => 137, 'max' => 198, 'year' => 2021],
        ['min' => 200, 'max' => 262, 'year' => 2022],
        ['min' => 263, 'max' => 324, 'year' => 2023],
        ['min' => 325, 'max' => 388, 'year' => 2024],
    ];
    private const array CONVOCATORIA_AUSENTE = [
        26, 45, 91, 114, 155, 180, 199, 260, 265,
        73, // Convocatoria 73 no se publicó por COVID-19
    ];

    private const array CONVOCATORIA_ULTIMA_PAGINA_UN_REGISTRO = [
        80, 95, 121, 124, 141, 158, 170, 171, 184, 215, 219, 229, 239, 268, 279, 293, 357
    ];

    /**
     * @throws Exception
     */
    public static function getCursoFromConvocatoria(int $convocatoria): CursoDto
    {
        if (in_array($convocatoria, self::CONVOCATORIA_AUSENTE, true)) {
            throw new Exception(
                sprintf('No existe ninguna convocatoria convocatoria con el número %d', $convocatoria)
            );
        }

        foreach (self::CONVOCATORIA_RANGES as $range) {
            if ($convocatoria >= $range['min'] && $convocatoria <= $range['max']) {
                return CursoDto::fromYear($range['year']);
            }
        }

        throw new Exception(
            sprintf('No se encontró curso para la convocatoria %d', $convocatoria)
        );
    }

}
