<?php

namespace App\Command;

use App\Entity\Centro;
use App\Entity\Convocatoria;
use App\Entity\Especialidad;
use App\Entity\Plaza;
use App\Enum\ObligatoriedadPlazaEnum;
use App\Enum\TipoPlazaEnum;
use App\Service\FileUtilitiesService;
use App\Service\SipriPlazasService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly FileUtilitiesService   $fileUtilitiesService,
        private readonly SipriPlazasService     $sipriPlazasService,
        private readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Este comando extrae plazas desde un PDF y las guarda en formato JSON');
        $this->addArgument('convocatoria', InputArgument::REQUIRED, 'Convocatoria a procesar');
    }

    /**
     * @throws \DateMalformedStringException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $convocatoria = $input->getArgument('convocatoria');
        $convocatoria = intval($convocatoria);

        $output->writeln('Convocatoria solicited: ' . $convocatoria);

        $path = 'pdfs/' . $convocatoria . '/';
        $pdfPath = $path . $convocatoria . '_plazas.pdf';
        $outputPath = $path . $convocatoria . '_plazas.json';

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

        foreach ($resultados as $plaza) {
            $plaza = new Plaza(
                Convocatoria::fromId($convocatoria),
                Centro::fromString($plaza['centro'], localidad: $plaza['localidad'], provincia: $plaza['provincia']),
                Especialidad::fromString($plaza['puesto']),
                TipoPlazaEnum::fromString($plaza['tipo']),
                ObligatoriedadPlazaEnum::fromString($plaza['voluntaria']),
                $plaza['fecha_prevista_cese'] == "" ? null : DateTime::createFromFormat('d/m/y', $plaza['fecha_prevista_cese']),
                intval($plaza['num_plazas'])
            );
            $this->entityManager->persist($plaza);
            $this->entityManager->flush();
            $this->entityManager->clear();
            $output->writeln('<info>Plaza añadida:</info> ' . $plaza->getCentro()->getNombre() . ' - ' . $plaza->getEspecialidad()->getNombre());
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
