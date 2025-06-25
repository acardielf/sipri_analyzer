<?php

namespace App\Command;

use App\Dto\CentroDto;
use App\Dto\ConvocatoriaDto;
use App\Dto\EspecialidadDto;
use App\Dto\PlazaDto;
use App\Enum\ObligatoriedadPlazaEnum;
use App\Enum\TipoPlazaEnum;
use App\Repository\PlazaRepository;
use App\Service\DtoToEntity\PlazaDtoToEntity;
use App\Service\FileUtilitiesService;
use App\Service\PlazasScrapperService;
use DateTimeImmutable;
use Exception;
use Smalot\PdfParser\Parser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'sipri:extraer-plazas',
    description: 'Extrae plazas desde un PDF y las guarda en formato JSON'
)]
class ExtraerPlazasCommand extends Command
{
    public function __construct(
        private readonly FileUtilitiesService  $fileUtilitiesService,
        private readonly PlazasScrapperService $plazasScrapperService,
        private readonly PlazaDtoToEntity      $plazaDtoToEntity,
        private readonly PlazaRepository       $plazaRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Este comando extrae plazas desde un PDF y las guarda en formato JSON');
        $this->addArgument('convocatoria', InputArgument::REQUIRED, 'Convocatoria a procesar');
        $this->addOption('info', 'info', InputOption::VALUE_NONE, 'Muestra información adicional sobre la convocatoria');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $convocatoria = $input->getArgument('convocatoria');
        $convocatoria = intval($convocatoria);

        $output->writeln('Procesando convocatoria: ' . $convocatoria);

        $path = FileUtilitiesService::getLocalPathForConvocatoria($convocatoria);
        $pdfPath = $path . $convocatoria . '_plazas.pdf';
        $outputPath = $path . $convocatoria . '_plazas.json';

        if (!$this->fileUtilitiesService->fileExists($pdfPath)) {
            $output->writeln('<error>Archivo PDF no encontrado.</error>');
            return Command::FAILURE;
        }

        $parser = new Parser();
        $pdf = $parser->parseContent($this->fileUtilitiesService->getFileContent($pdfPath));
        $text = $pdf->getText();

        $fechaConvocatoria = $this->plazasScrapperService->extractDateTimeFromText($text);
        $paginas = $this->plazasScrapperService->getPagesContentFromText($text);

        $resultados = [];


        foreach ($paginas as $numero => $contenido) {
            $resultadosPagina = $this->plazasScrapperService->extractPageContent($numero, $contenido, $convocatoria);
            $resultados = array_merge($resultados, $resultadosPagina);
        }

        $ocurrencia = 1;
        $nuevas = 0;

        $progressBar = new ProgressBar($output, sizeof($resultados));
        if (!$input->getOption('info')) {
            $progressBar->start();
        }

        foreach ($resultados as $plaza_array) {

            $plazaDto = new PlazaDto(
                id: null,
                convocatoria: ConvocatoriaDto::fromId($convocatoria, $fechaConvocatoria),
                centro: CentroDto::fromString($plaza_array['centro'], $plaza_array['localidad'], $plaza_array['provincia']),
                especialidad: EspecialidadDto::fromString($plaza_array['puesto']),
                tipoPlaza: TipoPlazaEnum::fromString($plaza_array['tipo']),
                obligatoriedadPlaza: ObligatoriedadPlazaEnum::fromString($plaza_array['voluntaria']),
                fechaPrevistaCese: $plaza_array['fecha_prevista_cese'] == "" ? null : DateTimeImmutable::createFromFormat('d/m/y', $plaza_array['fecha_prevista_cese']),
                numero: intval($plaza_array['num_plazas'])
            );

            $plaza = $this->plazaDtoToEntity->get($plazaDto, $ocurrencia);
            $texto = $plaza->getId() !== null ? '<comment>Plaza ya existe:</comment> ' : '<info>Plaza añadida:</info> ';

            if ($plaza->getId() == null) {
                $this->plazaRepository->save($plaza, clear: true);
                $nuevas++;
            }

            if ($input->getOption('info')) {
                $output->writeln($texto . ' ' . $plazaDto->centro->nombre . ' - ' . $plazaDto->especialidad->nombre);
            } else {
                $progressBar->advance();
            }

            $ocurrencia++;

        }

        if (!$input->getOption('info')) {
            $progressBar->finish();
            $output->writeln('');
        }


        // Guardar en JSON
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0775, true);
        }

        file_put_contents($outputPath, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $output->writeln('<info>Guardado:</info> ' . $outputPath . ' (' . count($resultados) . ' plazas)');

        $output->writeln('<info>Nuevas plazas:</info> ' . $nuevas);
        $output->writeln('<info>Plazas omitidas:</info> ' . ($ocurrencia-1 - $nuevas));
        $output->writeln('<info>Total plazas:</info> ' . $ocurrencia-1);

        return Command::SUCCESS;
    }

}
