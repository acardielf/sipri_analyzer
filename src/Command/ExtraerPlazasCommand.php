<?php

namespace App\Command;

use App\Service\FileUtilitiesService;
use Smalot\PdfParser\Parser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'sipri:extraer-plazas',
    description: 'Extrae plazas desde un PDF y las guarda en formato JSON'
)]
class ExtraerPlazasCommand extends Command
{
    public function __construct(
        private readonly FileUtilitiesService $fileUtilitiesService,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = 'pdfs/388/';
        $pdfPath = $path . 'plazas.pdf';
        $outputPath = $path . 'plazas.json';

        if (!$this->fileUtilitiesService->fileExists($pdfPath)) {
            $output->writeln('<error>Archivo PDF no encontrado.</error>');
            return Command::FAILURE;
        }

        $parser = new Parser();
        $pdf = $parser->parseContent($this->fileUtilitiesService->getFileContent($pdfPath));
        $text = $pdf->getText();

        // Buscar todas las posiciones donde aparece un "xxxxxxxx - "
        preg_match_all('/\d{8} - /', $text, $coincidencias, PREG_OFFSET_CAPTURE);

        dd($coincidencias);

        $posiciones = array_map(fn($c) => $c[1], $coincidencias[0]);
        $resultados = [];

        for ($i = 0; $i < count($posiciones) - 1; $i += 2) {
            $inicio = $posiciones[$i];
            $fin = $posiciones[$i + 1];
            $bloque = substr($text, $inicio, $fin - $inicio);

            $linea = trim(preg_replace('/\s+/', ' ', $bloque));

            $fila = [
                'codigo' => null,
                'centro' => null,
                'localidad' => null,
                'provincia' => null,
                'tipo' => null,
                'plazas' => null,
                'fecha_cese' => null,
                'puesto' => null,
            ];

            if (preg_match('/^(?<codigo>\d{8}) - (?<centro>.+?)(?=\d+ - |\s+[A-Z]{2}| Sustitución | Vacante )/u', $linea, $m)) {
                $fila['codigo'] = $m['codigo'];
                $fila['centro'] = trim($m['centro']);
            }

            if (preg_match('/(\d+ - [\p{L} .]+)(?= \d+ - | Sustitución | Vacante )/u', $linea, $m)) {
                $fila['localidad'] = trim($m[1]);
            }

            if (preg_match('/(Sustitución|Vacante)/', $linea, $m)) {
                $fila['tipo'] = $m[1];
            }

            if (preg_match('/(\d{2}\/\d{2}\/\d{2})/', $linea, $m)) {
                $fila['fecha_cese'] = $m[1];
            }

            if (preg_match('/(\d+)(?!.*\d)/', $linea, $m)) {
                $fila['plazas'] = (int)$m[1];
            }

            if (preg_match('/(\d{8,} - [^0-9]+)/', $linea, $m)) {
                $fila['puesto'] = trim($m[1]);
            }

            $resultados[] = $fila;
        }

        // Guardar en JSON
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0775, true);
        }

        file_put_contents($outputPath, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $output->writeln('<info>Guardado:</info> ' . $outputPath . ' (' . count($resultados) . ' plazas)');

        return Command::SUCCESS;
    }
}
