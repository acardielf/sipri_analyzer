<?php

namespace App\Command;

use App\Entity\Adjudicacion;
use App\Entity\Centro;
use App\Entity\Plaza;
use App\Enum\TipoPlazaEnum;
use App\Enum\TipoProcesoEnum;
use App\Repository\PlazaRepository;
use App\Service\FileUtilitiesService;
use App\Service\ScrapperService;
use App\Service\TabulaPythonService;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
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
        private readonly TabulaPythonService $tabulaService,
    ) {
        parent::__construct();
    }

    /**
     * @param int $convocatoria
     * @param mixed $adjudicaciones_array
     * @return Plaza[]|null
     */
    public function getPlazaObjetivoIfExists(int $convocatoria, mixed $adjudicaciones_array): ?array
    {
        return $this->plazaRepository->findByAttributes(
            convocatoriaId: $convocatoria,
            centroId: $adjudicaciones_array['centro'],
            especialidadId: $adjudicaciones_array['puesto'],
            tipo: TipoPlazaEnum::fromString($adjudicaciones_array['tipo']),
            //obligatoriedad: ObligatoriedadPlazaEnum::fromString($adjudicaciones_array['voluntaria']),
            fechaPrevistaCese: $adjudicaciones_array['fecha_prevista_cese'] == "" ? null : DateTimeImmutable::createFromFormat(
                '!d/m/y',
                $adjudicaciones_array['fecha_prevista_cese']
            )
        );
    }

    protected function configure(): void
    {
        $this->setHelp('Este comando extrae plazas desde un PDF y las guarda en formato JSON');
        $this->addArgument('convocatoria', InputArgument::REQUIRED, 'Convocatoria a procesar');
        $this->addOption(
            'pagina',
            'p',
            InputOption::VALUE_OPTIONAL,
            'Número de página a procesar (opcional, por defecto procesa todas las páginas)',
            null
        );
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

        $output->writeln('Extrayendo adjudicaciones para la convocatoria: ' . $convocatoria);

        $path = FileUtilitiesService::getLocalPathForConvocatoria($convocatoria);
        $pdfPath = $path . $convocatoria . '_adjudicados.pdf';

        if (!$this->fileUtilitiesService->fileExists($pdfPath)) {
            $output->writeln('<error>Archivo PDF no encontrado.</error>');
            return Command::FAILURE;
        }

        $json = $this->tabulaService->generateJsonFromPdf(
            TipoProcesoEnum::ADJUDICACION,
            $convocatoria,
            $pdfPath,
        );

        $resultados = [];
        foreach ($json as $numero => $contenido) {
            $resultadosPagina = $this->scrapperService->extractAdjudicacionFromPageContent(
                $numero,
                $contenido,
                $convocatoria
            );
            $resultados = array_merge($resultados, $resultadosPagina);
        }

        $progressBar = new ProgressBar($output, sizeof($resultados));
        if (!$input->getOption('info')) {
            $progressBar->start();
        }

        $omitidas = 0;
        $nuevas = 0;
        $noEncontradas = 0;
        $ocep = 0;

        foreach ($resultados as $index => $adjudicaciones_array) {
            $plazaObjetivo = $this->getPlazaObjetivoIfExists($convocatoria, $adjudicaciones_array);

            if (empty($plazaObjetivo)) {
                if (in_array($adjudicaciones_array['centro'], Centro::OCEP_OTROS_CENTROS)) {
                    $ocep++;
                } else {
                    if ($input->getOption('info')) {
                        $output->writeln(
                            '<error>No se ha encontrado ninguna plaza para los criterios especificados.</error>'
                        );
                        $output->writeln('<info>Criterios: ' . json_encode($adjudicaciones_array));
                    }
                    $noEncontradas++;
                }
                continue;
            }

            $plaza = null;
            if (count($plazaObjetivo) > 1) {
                if ($input->getOption('info')) {
                    $output->writeln(
                        '<info>Ambigüedad: Más de una plaza encontrada para los criterios especificados.</info>',
                    );
                    $output->writeln('<info>Criterios: ' . json_encode($adjudicaciones_array));
                }
                foreach ($plazaObjetivo as $plazaComprueba) {
                    $plaza = $plazaComprueba;
                    if ($plazaComprueba->adjudicadaCompletamente()) {
                        $omitidas++;
                    } else {
                        break;
                    }
                }
            } else {
                $plaza = $plazaObjetivo[0];
            }


            if (!$plaza->adjudicadaCompletamente()) {
                $plaza->addAdjudicacion(
                    new Adjudicacion(
                        id: null,
                        puesto: intval($adjudicaciones_array['orden']),
                        plaza: $plazaObjetivo[0],
                    )
                );
                $nuevas++;
            } else {
                $omitidas++;
            }

            $this->plazaRepository->save($plaza, clear: true);

            if (!$input->getOption('info')) {
                $progressBar->advance();
            }
        }


        if (!$input->getOption('info')) {
            $progressBar->finish();
            $output->writeln('');
        }

        $output->writeln('');
        $output->writeln('<info>No asociadas: ' . $noEncontradas);
        $output->writeln('OCEP - Servicios otros centros: ' . $ocep);
        $output->writeln('Omitidas: ' . $omitidas);
        $output->writeln('Nuevas: ' . $nuevas . '</info>');

        return Command::SUCCESS;
    }

}
