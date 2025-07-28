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

        foreach ($records as $index => $record) {
            $data[] = [
                'centro' => $record[0] ?? '',
                'localidad' => $record[1] ?? '',
                'provincia' => $record[2] ?? '',
                'puesto' => $record[3] ?? '',
                'tipo' => $record[4] ?? '',
                'num_plazas' => $record[5] ?? '',
                'voluntaria' => $record[6] ?? '',
                'fecha_prevista_cese' => $record[7] ?? '',
                'pagina' => $pagina + 1,
                'fila' => $index + 1,
                'convocatoria' => $convocatoria,
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
                'centro' => explode(' - ', $record[2])[0] ?? '',
                'localidad' => $record[3] ?? '',
                'provincia' => $record[4] ?? '',
                'puesto' => explode(' - ', $record[5])[0] ?? '',
                'tipo' => $record[6] ?? '',
                'fecha_prevista_cese' => $record[7] ?? '',
                'voluntaria' => $record[8] ?? '',
            ];
        }

        return $data;
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
