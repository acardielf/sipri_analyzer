<?php

namespace App\Service;

use App\Enum\ProvinciaEnum;
use App\Enum\TipoPlazaEnum;
use DateTimeImmutable;

class ScrapperService
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

    public function extractPlazasFromPageContent(int $pagina, array $records, int $convocatoria): array
    {
        $data = [];

        foreach ($records as $record) {
            $data[] = [
                'centro' => explode(' - ',$record[0])[0]  ?? '',
                'localidad' => explode(' - ',$record[1])[1]  ?? '',
                'provincia' => explode(' - ',$record[2])[0]  ?? '',
                'puesto' => explode(' - ',$record[3])[0] ?? '',
                'tipo' => $record[4] ?? '',
                'num_plazas' => $record[5] ?? '',
                'voluntaria' => $record[6] ?? '',
                'fecha_prevista_cese' => $record[7] ?? '',
            ];
        }

        return $data;
    }


    public function extractAdjudicacionFromPageContent(int $pagina, array $records, int $convocatoria): array
    {
        $data = [];

        foreach ($records as $record) {
            $data[] = [
                'orden' => $record[1] ?? '',
                'centro' => explode(' - ',$record[2])[0]  ?? '',
                'localidad' => $record[3] ?? '',
                'provincia' => $record[4] ?? '',
                'puesto' => explode(' - ',$record[5])[0] ?? '',
                'tipo' => $record[6] ?? '',
                'fecha_prevista_cese' => $record[7] ?? '',
                'voluntaria' => $record[8] ?? '',
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

    private function extractPlazasNormalCase(int $pagina, int $convocatoria, array $lines): array
    {
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
        foreach ($lines as $line) {
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

        //check all arrays have the same count
        if (
            count($centros) !== $count ||
            count($localidades) !== $count ||
            count($provincias) !== $count ||
            count($puestos) !== $count ||
            count($tipos) !== $count ||
            count($plazas) !== $count ||
            count($fechas) !== $count ||
            count($obligatoriedad) !== $count
        ) {
            throw new \RuntimeException(
                'Los datos extraídos de la pagina ' . $pagina . ' convocatoria ' . $convocatoria . ' no tienen el mismo número de elementos: ' .
                'centros: ' . count($centros) . ', ' .
                'localidades: ' . count($localidades) . ', ' .
                'provincias: ' . count($provincias) . ', ' .
                'puestos: ' . count($puestos) . ', ' .
                'tipos: ' . count($tipos) . ', ' .
                'plazas: ' . count($plazas) . ', ' .
                'fechas: ' . count($fechas) . ', ' .
                'obligatoriedad: ' . count($obligatoriedad)
            );
        }

        return [
            'count' => $count,
            'pagina' => $pagina,
            'convocatoria' => $convocatoria,
            'centros' => $centros,
            'localidades' => $localidades,
            'provincias' => $provincias,
            'puestos' => $puestos,
            'tipos' => $tipos,
            'plazas' => $plazas,
            'obligatoriedad' => $obligatoriedad,
            'fechas' => $fechas
        ];
    }

    private function extractAdjudicacionNormalCase(int $pagina, int $convocatoria, array $lines): array
    {
        /**
         * 1 - Apellidos, nombre y NIF/NIE
         * 2 - Orden
         * 3 - Centro
         * 4 - Localidad
         * 5 - Provincia
         * 6 - Puesto
         * 7 - Tipo plaza
         * 8 - F. Prevista Cese
         * 9 - AND
         */

        $obligatoriedad = [];
        foreach ($lines as $line) {
            if (preg_match('/^[SNV](\/[SNV])?$/', $line)) {
                $obligatoriedad[] = $line;
            }
        }
        $count = count($obligatoriedad) / 2;

        /*
         * Bloque 1: Apellidos, Nombre y NIF/NIE
         * Se omite por privacidad y protección de datos personales
         * Buscamos la primera línea que contenga un número de 1 a 99999
         * Que serán las posiciones de los adjudicatarios
         */
        $indice = null;
        foreach ($lines as $i => $valor) {
            if ($valor === "" || preg_match('/^[1-9][0-9]{0,4}$/', $valor)) {
                $indice = $i;
                break;
            }
        }

        /*
         * Bloque 2: Orden
         */
        $index_orden_start = $indice;
        $index_orden_end = $indice + $count - 1;


        /*
         * Bloque: Posición/Especialidad
         *
         * Buscamos desde el final la primera línea que tenga una fecha y hora y ponemos el fin 2 posiciones antes
         * Si no aparece esa fecha, o aparece muy lejos (más de $count líneas), tomamos el final como la última línea
         */
        $indice = null;
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4} {3}\d{2}:\d{2}$/', $lines[$i])) {
                $indice = $i;
                break;
            }
        }
        if ($indice === null || (count($lines) - $indice) > $count) {
            $index_puesto_end = count($lines) - 1;
        } else {
            $index_puesto_end = $indice - 2;
        }


        $indice = null;
        for ($i = $index_puesto_end; $i >= 0; $i--) {
            if ($lines[$i] === "N" || $lines[$i] === "S") {
                $indice = $i;
                break;
            }
        }
        $index_puesto_start = $indice + 1;

        if (!preg_match('/^[A-Za-z0-9]{8} - /', $lines[$index_puesto_start])) {
            // Estamos ante un salto de página. Es decir, un puesto ha sido dividido en 2 páginas
            // por lo que omitimos la primera fila si no empieza por el código de la especialidad
            // caso ejemplo: Adjudicacion 3. Página 10
            $index_puesto_start += 1;
        }

        $index_obligatoriedad_end = $indice;
        $index_obligatoriedad_start = $index_obligatoriedad_end - $count + 1;

        $index_fecha_end = $index_obligatoriedad_start - 1;
        $index_fecha_start = $index_fecha_end - $count + 1;

        $index_tipo_end = $index_fecha_start - 1;
        $index_tipo_start = $index_tipo_end - $count + 1;

        $index_provincia_end = $index_tipo_start - 1;
        $index_provincia_start = $index_provincia_end - $count + 1;

        $codigos_centros = [];
        for ($i = $index_orden_end + 1; $i < $index_provincia_start; $i++) {
            if (preg_match('/^([A-Za-z0-9]{8}) - /', $lines[$i], $matches)) {
                $codigos_centros[] = $matches[1]; // Solo el código de centro
            }
        }

        $centros = $codigos_centros;
        $provincias = array_slice($lines, $index_provincia_start, $index_provincia_end - $index_provincia_start + 1);
        $tipos = array_slice($lines, $index_tipo_start, $index_tipo_end - $index_tipo_start + 1);
        $fechas = array_slice($lines, $index_fecha_start, $index_fecha_end - $index_fecha_start + 1);
        $puesto = array_slice($lines, $index_puesto_start, $index_puesto_end - $index_puesto_start + 1);
        $orden = array_slice($lines, $index_orden_start, $index_orden_end - $index_orden_start + 1);
        $obligatoriedad = array_slice(
            $lines,
            $index_obligatoriedad_start,
            $index_obligatoriedad_end - $index_obligatoriedad_start + 1
        );

        $puestos = $this->fixDataDependingType($puesto, $count, 'puesto');

        // extraer solo el codigo de puesto (antes del - )
        $puestos = array_map(function ($puesto) {
            if (preg_match('/^([A-Za-z0-9]{8}) - /', $puesto, $matches)) {
                return $matches[1]; // Solo el código de puesto
            }
            return '';
        }, $puestos);


        //check all arrays have the same count
        if (
            count($centros) !== $count ||
            count($provincias) !== $count ||
            count($puestos) !== $count ||
            count($tipos) !== $count ||
            count($fechas) !== $count ||
            count($orden) !== $count ||
            count($obligatoriedad) !== $count
        ) {
            throw new \RuntimeException(
                'Los datos extraídos de la pagina ' . $pagina . ' convocatoria ' . $convocatoria . ' no tienen el mismo número de elementos: ' .
                'centros: ' . count($centros) . ', ' .
                'provincias: ' . count($provincias) . ', ' .
                'puestos: ' . count($puestos) . ', ' .
                'tipos: ' . count($tipos) . ', ' .
                'fechas: ' . count($fechas) . ', ' .
                'orden: ' . count($orden) . ', ' .
                'obligatoriedad: ' . count($obligatoriedad)
            );
        }

        return [
            'count' => $count,
            'pagina' => $pagina,
            'convocatoria' => $convocatoria,
            'centros' => $centros,
            'provincias' => $provincias,
            'puestos' => $puestos,
            'tipos' => $tipos,
            'orden' => $orden,
            'obligatoriedad' => $obligatoriedad,
            'fechas' => $fechas
        ];
    }

    protected function extractProvincia(array $lines): ?string
    {
        foreach ($lines as $line) {
            foreach (ProvinciaEnum::cases() as $province) {
                if (stripos($line, $province->getWithCode()) !== false) {
                    return $province->getWithCode();
                }
            }
        }

        return null;
    }


    protected function extractTipo(array $lines): ?string
    {
        foreach ($lines as $line) {
            foreach (TipoPlazaEnum::cases() as $tipo) {
                if (stripos($line, $tipo->getLabel()) !== false) {
                    return $tipo->getLabel();
                }
            }
        }

        return null;
    }

    private function extractPlazasOnlyOneRecord(int $pagina, int $convocatoria, array $lines): array
    {
        $parts = array_merge(...array_map(fn($line) => explode("\t", $line), $lines));

        $provincia = $this->extractProvincia($parts);
        $tipo = $this->extractTipo($parts);

        $parts = $this->removeFoundedValuesFromLines($parts, [$provincia, $tipo]);

        $localidad = $this->extractLocalidad($parts);

        $numeroPlazas = $this->extractNumeroPlazas($parts);
        $fecha = $this->extractFecha($parts);

        $parts = $this->removeFoundedValuesFromLines($parts, [$localidad]);
        $parts = $this->removeFoundedValuesFromLines($parts, [$fecha, $numeroPlazas], strict: true);

        $obligatoriedad = $this->extractObligatoriedad(reset($parts));
        $centro = $this->extractCentro(reset($parts));
        $especialidad = $this->extractEspecialidad(end($parts));


        return [
            'count' => 1,
            'pagina' => $pagina,
            'convocatoria' => $convocatoria,
            'centros' => [$centro],
            'localidades' => [$localidad],
            'provincias' => [$provincia],
            'puestos' => [$especialidad],
            'tipos' => [$tipo],
            'plazas' => [$numeroPlazas],
            'obligatoriedad' => [$obligatoriedad],
            'fechas' => [$fecha]
        ];
    }

    private function removeFoundedValuesFromLines(array $lines, array $encontrados, bool $strict = false): array
    {
        $sanitized = [];
        foreach ($lines as $line) {
            $sanitizedLine = $line;
            foreach ($encontrados as $toRemove) {
                if ($strict) {
                    if ($toRemove == $sanitizedLine) {
                        $sanitizedLine = "";
                    }
                } else {
                    $sanitizedLine = str_replace($toRemove, "", $sanitizedLine);
                }
            }
            $sanitized[] = $sanitizedLine;
        }

        return array_filter($sanitized, fn($line) => trim($line) !== '');
    }

    private function extractLocalidad(array $lines): ?string
    {
        $lines = array_values($lines);
        foreach ($lines as $index => $line) {
            if (preg_match('/(?<!\d)\d{1,2} - .+$/', $line, $coincidencias)) {
                $resultado = $coincidencias[0];
                if ($index !== sizeof($lines) - 1 && !preg_match('/^\d/', $lines[$index + 1])) {
                    // Si la siguiente línea no empieza con un número, es parte de la localidad
                    $resultado .= ' ' . $lines[$index + 1];
                }
            }
        }
        return $resultado ?? null;
    }

    private function extractNumeroPlazas(array $lines): ?int
    {
        foreach ($lines as $line) {
            if (preg_match('/^(\d{2}\/\d{2}\/\d{2})(\d)$/', $line, $matches) && isset($matches[2])) {
                return (int)$matches[2];
            }

            if (ctype_digit($line)) {
                return (int)$line;
            }
        }
        return null;
    }

    private function extractFecha(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/^(\d{2}\/\d{2}\/\d{2})(\d)$/', $line, $matches) && isset($matches[1])) {
                return $matches[1];
            }
        }
        return null;
    }

    private function extractObligatoriedad(string $reset): ?string
    {
        $parts = explode(' - ', $reset);

        if (count($parts) < 2) {
            return null;
        }
        $obligatoriedad = trim($parts[0]);

        if (empty($obligatoriedad)) {
            return null;
        }

        return $obligatoriedad[0];
    }


    private function extractCentro(string $string): ?string
    {
        $result = $this->extractCentroOrEspecialidad($string);
        if ($result === null) {
            return null;
        }
        // Elimina espacios y dígitos al final de la cadena
        $limpia = preg_replace('/[\s\d]+$/', '', $result);
        return substr($limpia, 1);
    }

    private function extractEspecialidad(string $string): ?string
    {
        $result = $this->extractCentroOrEspecialidad($string);
        if ($result === null) {
            return null;
        }
        return $result;
    }

    private function extractCentroOrEspecialidad(string $string): ?string
    {
        $parts = explode(' - ', $string);
        if (count($parts) < 2) {
            return null;
        }
        $code = trim($parts[0]);
        $rest = trim($parts[1]);

        if (empty($rest) || empty($code)) {
            return null;
        }

        return $code . " - " . $rest;
    }

    public function extractDateTimeFromText(string $text): ?\DateTimeImmutable
    {
        $pattern = '/^(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2})/';

        if (preg_match($pattern, $text, $matches)) {
            $dateTime = $matches[0];
            return DateTimeImmutable::createFromFormat('d/m/Y H:i', $dateTime);
        }
        return null;
    }

    private function extractAdjudicacionOnlyOneRecord(int $pagina, int $convocatoria, array $lines): array
    {
        $parts = array_merge(...array_map(fn($line) => explode("\t", $line), $lines));

        $codigos = [];
        foreach ($lines as $line) {
            preg_match_all('/(\d{8})(?=\s*-\s*.+)/', $line, $matches);
            $codigos = array_merge($codigos, $matches[1]);
        }

        $centro = $codigos[0] ?? null;
        $puesto = $codigos[1] ?? null;

        $parts = $this->removeFoundedValuesFromLines($parts, [$centro, $puesto]);

        $orden = null;
        foreach ($parts as $line) {
            if (preg_match('/\s(\d{1,5})\b/', $line, $match)) {
                $orden = $match[1];
                break;
            }
        }
        $parts = $this->removeFoundedValuesFromLines($parts, [$orden]);

        $resultado = [];
        foreach ($lines as $line) {
            if (preg_match_all('/\b([NSV])\b/', $line, $matches)) {
                $resultado = array_merge($resultado, $matches[1]);
            }
        }

        $tipo = $resultado[0] ?? null;
        $obligatoriedad = $resultado[1] ?? null;

        return [
            'count' => 1,
            'pagina' => $pagina,
            'convocatoria' => $convocatoria,
            'centros' => [$centro],
            'provincias' => [""],
            'puestos' => [$puesto],
            'tipos' => [$tipo],
            'orden' => [$orden],
            'obligatoriedad' => [$obligatoriedad],
        ];
    }

}
