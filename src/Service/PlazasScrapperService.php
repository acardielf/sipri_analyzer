<?php

namespace App\Service;

class PlazasScrapperService
{

    public function getPagesContentFromText(string $text): array
    {
        preg_match_all('/Página (\d+) de (\d+)/', $text, $matches, PREG_OFFSET_CAPTURE);

        $paginas = [];
        $numeroPaginas = count($matches[0]);

        for ($i = 0; $i < $numeroPaginas - 1; $i++) {
            $inicio = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $fin = $matches[0][$i + 1][1];

            $contenidoPagina = substr($text, $inicio, $fin - $inicio);
            $paginaNumero = $matches[1][$i][0];

            $paginas[$paginaNumero] = trim($contenidoPagina);
        }

        // Opcional: última página (después de la última marca hasta el final del texto)
        $ultimaPaginaNumero = $matches[1][$numeroPaginas - 1][0];
        $inicioUltima = $matches[0][$numeroPaginas - 1][1] + strlen($matches[0][$numeroPaginas - 1][0]);
        $paginas[$ultimaPaginaNumero] = trim(substr($text, $inicioUltima));

        return $paginas;
    }

    public function extractPageContent($content): array
    {
        $lines = explode("\n", $content);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, fn($line) => !str_starts_with($line, 'F. Prev. Cese Puesto'));
        $lines = array_values($lines); // Reindexar

        // Eliminar cabecera si está presente
        if (preg_match('/VoluntariaCentro.*Localidad.*Provincia.*Tipo.*Nº Plazas/i', $lines[0])) {
            array_shift($lines);
        }


        /**
         * 1 - Voluntaria
         * 2 - Centro
         * 3 - Localidad
         * 4 - Provincia
         * 5 - Tipo
         * 6 - Fecha prevista cese
         * 7 - Nº Plazas
         * 8 - Puesto
         */

        $obligatoriedad = [];
        foreach ($lines as $i => $line) {
            if (preg_match('/^[SN](\/[SN])?$/', $line)) {
                $obligatoriedad[] = $line;
            } else {
                break;
            }
        }
        $count = count($obligatoriedad);

        // Bloque 1: Obligatoriedad (estable)
        $index_obligatoriedad = 0;

        // Bloque 2: Centro (NO ESTABLE)
        $index_centro_start = $index_obligatoriedad + $count;
        $index_centro_end = $this->findFirstInvalidCodigoCentro($lines, $index_centro_start) - 1;

        //Bloque 8: Puesto (NO estable)
        $index_puesto_end = $this->findLastFechaHoraLineIndexOrLastLine($lines) - 1;
        $index_puesto_start = $this->findFirstNumericLineFrom($lines, $index_puesto_end) + 1;

        //Bloque 7: Nº Plazas (estable)
        $index_plazas = $index_puesto_start - $count;

        //Bloque 6: Fecha prevista cese (estable)
        $index_fecha = $index_plazas - $count;

        //Bloque 5: Tipo
        $index_tipo = $index_fecha - $count;

        //Bloque 4: Provincia
        $index_provincia = $index_tipo - $count;

        //Bloque 3: Localidad
        $index_localidad_start = $index_centro_end + 1;
        $index_localidad_end = $index_provincia - 1;

        $centros = array_slice($lines, $index_centro_start, $index_centro_end - $index_centro_start + 1);
        $localidades = array_slice($lines, $index_localidad_start, $index_localidad_end - $index_localidad_start + 1);
        $provincias = array_slice($lines, $index_provincia, $count);
        $tipos = array_slice($lines, $index_tipo, $count);
        $fechas = array_slice($lines, $index_fecha, $count);
        $plazas = array_slice($lines, $index_plazas, $count);
        $puestos = array_slice($lines, $index_puesto_start, $index_puesto_end - $index_puesto_start + 1);

        $centros = $this->fixDataDependingType($centros, $count, 'centro');
        $localidades = $this->fixDataDependingType($localidades, $count, 'localidad');
        $puestos = $this->fixDataDependingType($puestos, $count, 'puesto');

        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'centro' => $centros[$i] ?? '',
                'localidad' => $localidades[$i] ?? '',
                'provincia' => $provincias[$i] ?? '',
                'puesto' => $puestos[$i] ?? '',
                'tipo' => $tipos[$i] ?? '',
                'num_plazas' => $plazas[$i] ?? '',
                'voluntaria' => $obligatoriedad[$i] ?? '',
                'fecha_prevista_cese' => $fechas[$i] ?? '',
            ];
        }

        return $data;
    }

    public function fixDataDependingType(array $lines, int $count, string $type): array
    {
        $result = [];
        $actual = '';

        match ($type) {
            'localidad' => $pattern = '/^\d+\s*-\s*/',
            'centro' => $pattern = '/^\d{8}+\s*-\s*/',
            'puesto' => $pattern = '/^[a-zA-Z0-9]{8}\s*-\s*/',
            default => throw new \InvalidArgumentException("Tipo no soportado: $type"),
        };

        foreach ($lines as $line) {
            if (preg_match($pattern, $line)) {
                if ($actual !== '') {
                    $result[] = trim($actual);
                }
                $actual = $line;
            } else {
                $actual .= ' ' . $line;
            }
        }
        if ($actual !== '') {
            $result[] = trim($actual);
        }

        while (count($result) < $count) {
            $result[] = '';
        }
        return $result;
    }

    protected function findLastFechaHoraLineIndexOrLastLine(array $lines): ?int
    {
        $pattern = '/\b\d{2}\/\d{2}\/\d{4}[\t ]+\d{2}:\d{2}\b/';
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (preg_match($pattern, $lines[$i])) {
                return $i; // Devuelve el índice de línea
            }
        }
        return count($lines); // No se encontró
    }

    protected function findFirstNumericLineFrom(array $lines, int $start): ?int
    {
        for ($i = $start; $i >= 0; $i--) {
            if (preg_match('/^\d+$/', trim($lines[$i]))) {
                return $i;
            }
        }
        return null;
    }

    protected function findFirstInvalidCodigoCentro(array $lines, int $start): ?int
    {
        // Patrón que indica el inicio del siguiente bloque: hasta 5 dígitos + ' - '
        $patternSiguiente = '/^\d{1,5} - /';

        for ($i = $start; $i < count($lines); $i++) {
            $line = trim($lines[$i]);

            // Si coincide con el patrón del siguiente bloque, devolvemos esta línea
            if (preg_match($patternSiguiente, $line)) {
                return $i;
            }
        }

        return null;
    }

}
