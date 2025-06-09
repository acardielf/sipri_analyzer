<?php

namespace App\Command;

use App\Service\FileUtilitiesService;
use App\Service\SipriPlazasService;
use Smalot\PdfParser\Parser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
        private readonly SipriPlazasService   $sipriPlazasService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Este comando extrae plazas desde un PDF y las guarda en formato JSON');
        $this->addArgument('convocatoria', InputArgument::REQUIRED, 'Convocatoria a procesar');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $convocatoria = $input->getArgument('convocatoria');
        $convocatoria = intval($convocatoria);

        $output->writeln('Convocatoria solicited: ' . $convocatoria);

        $path = 'pdfs/' . $convocatoria . '/';
        $pdfPath = $path . 'plazas.pdf';
        $outputPath = $path . 'plazas.json';

        if (!$this->fileUtilitiesService->fileExists($pdfPath)) {
            $output->writeln('<error>Archivo PDF no encontrado.</error>');
            return Command::FAILURE;
        }

        $parser = new Parser();
        $pdf = $parser->parseContent($this->fileUtilitiesService->getFileContent($pdfPath));
        $text = $pdf->getText();

        $paginas = $this->sipriPlazasService->getPagesContentFromText($text);

        $resultados = [];


        foreach ($paginas as $numero => $contenido) {
            $resultadosPagina = $this->sipriPlazasService->extractPageContent($contenido);
//            $this->fileUtilitiesService->saveContentToFile(
//                $path . 'plazas_pag_' . $numero . '.txt',
//                $paginas[$numero]
//            );
            $resultados = array_merge($resultados, $resultadosPagina);
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
