<?php

namespace App\Command;

use App\Dto\CentroDto;
use App\Dto\ConvocatoriaDto;
use App\Dto\EspecialidadDto;
use App\Dto\PlazaDto;
use App\Enum\ObligatoriedadPlazaEnum;
use App\Enum\TipoPlazaEnum;
use App\Repository\PlazaRepository;
use App\Service\FileUtilitiesService;
use App\Service\ScrapperService;
use DateTimeImmutable;
use Exception;
use Smalot\PdfParser\Parser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'sipri:adj',
    description: 'Extrae adjudicaciones desde un PDF y las guarda en formato JSON',
)]
class ExtraerAdjudicacionesCommand extends Command
{
    public function __construct(
        private readonly FileUtilitiesService $fileUtilitiesService,
        private readonly ScrapperService $scrapperService,
        private readonly PlazaRepository $plazaRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Este comando extrae plazas desde un PDF y las guarda en formato JSON');
        $this->addArgument('convocatoria', InputArgument::REQUIRED, 'Convocatoria a procesar');
        $this->addOption(
            'info',
            'info',
            InputOption::VALUE_NONE,
            'Muestra información adicional sobre la convocatoria'
        );
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
        $pdfPath = $path . $convocatoria . '_adjudicados.pdf';


        if (!$this->fileUtilitiesService->fileExists($pdfPath)) {
            $output->writeln('<error>Archivo PDF no encontrado.</error>');
            return Command::FAILURE;
        }

        $parser = new Parser();
        $pdf = $parser->parseContent($this->fileUtilitiesService->getFileContent($pdfPath));
        $text = $pdf->getText();
        //$text = Pdf::getText($pdfPath);

        $fechaAdjudicacion = $this->scrapperService->extractDateTimeFromText($text);
        $paginas = $this->scrapperService->getPagesContentFromText($text);

        $resultados = [];
        foreach ($paginas as $numero => $contenido) {
            $resultadosPagina = $this->scrapperService->extractAdjudicacionFromPageContent(
                $numero,
                $contenido,
                $convocatoria
            );
            $resultados = array_merge($resultados, $resultadosPagina);
        }

        foreach ($resultados as $adjudicaciones_array) {
            $plazaObjetivo = $this->plazaRepository->findByAttributes(
                convocatoriaId: $convocatoria,
                centroId: $adjudicaciones_array['centro'],
                especialidadId: $adjudicaciones_array['puesto'],
                tipo: $adjudicaciones_array['tipo'],
                obligatoriedad: $adjudicaciones_array['voluntaria'],
                fechaPrevistaCese: $adjudicaciones_array['fecha_prevista_cese'] == "" ? null : DateTimeImmutable::createFromFormat(
                    'd/m/y',
                    $adjudicaciones_array['fecha_prevista_cese']
                )
            );

            if ($plazaObjetivo !== null) {
                $output->writeln('<comment>Plaza ya existe:</comment> ' . $plazaObjetivo->getId());
                continue;
            }


            $plazaDto = new PlazaDto(
                id: null,
                convocatoria: ConvocatoriaDto::fromId($convocatoria, fecha: null),
                centro: CentroDto::fromString(
                    $adjudicaciones_array['centro'],
                    $adjudicaciones_array['localidad'],
                    $adjudicaciones_array['provincia']
                ),
                especialidad: EspecialidadDto::fromString($adjudicaciones_array['puesto']),
                tipoPlaza: TipoPlazaEnum::fromString($adjudicaciones_array['tipo']),
                obligatoriedadPlaza: ObligatoriedadPlazaEnum::fromString($adjudicaciones_array['voluntaria']),
                fechaPrevistaCese: $adjudicaciones_array['fecha_prevista_cese'] == "" ? null : DateTimeImmutable::createFromFormat(
                    'd/m/y',
                    $adjudicaciones_array['fecha_prevista_cese']
                ),
                numero: intval($adjudicaciones_array['num_plazas'])
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


        return Command::SUCCESS;
    }

}
