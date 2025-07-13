<?php

namespace App\Service;

use App\Enum\ProvinciaEnum;
use App\Enum\TipoPlazaEnum;
use DateTimeImmutable;

class ScrapperService
{

    public function extractPlazasFromPageContent(int $pagina, array $records, int $convocatoria): array
    {
        $data = [];

        foreach ($records as $record) {
            $data[] = [
                'centro' => $record[0]  ?? '',
                'localidad' => $record[1]  ?? '',
                'provincia' => $record[2]  ?? '',
                'puesto' => $record[3] ?? '',
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

}
