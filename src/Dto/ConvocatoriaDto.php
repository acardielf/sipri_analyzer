<?php

namespace App\Dto;

use Exception;

readonly class ConvocatoriaDto
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


    public function __construct(
        public string   $id,
        public string   $nombre,
        public CursoDto $curso,
    )
    {
    }

    /**
     * @throws Exception
     */
    public static function fromId(int $convocatoria): ConvocatoriaDto
    {
        return new ConvocatoriaDto(
            id: (string)$convocatoria,
            nombre: str_pad((string)$convocatoria, 12, '0', STR_PAD_LEFT),
            curso: static::getCursoFromConvocatoria($convocatoria),
        );
    }

    /**
     * @throws Exception
     */
    public static function getCursoFromConvocatoria(int $convocatoria): CursoDto
    {
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
