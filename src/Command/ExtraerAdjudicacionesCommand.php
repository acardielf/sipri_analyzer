<?php

namespace App\Command;

use App\Entity\Adjudicacion;
use App\Entity\Centro;
use App\Entity\Plaza;
use App\Enum\TipoPlazaEnum;
use App\Enum\TipoProcesoEnum;
use App\Repository\AdjudicacionRepository;
use App\Repository\ConvocatoriaRepository;
use App\Repository\PlazaRepository;
use App\Service\FileUtilitiesService;
use App\Service\ScrapperService;
use App\Service\TabulaPythonService;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'sipri:adj',
    description: 'Extrae adjudicaciones desde un PDF y las guarda en formato JSON',
)]
readonly class ExtraerAdjudicacionesCommand
{
    public function __construct(
        private FileUtilitiesService $fileUtilitiesService,
        private ScrapperService $scrapperService,
        private PlazaRepository $plazaRepository,
        private TabulaPythonService $tabulaService,
        private AdjudicacionRepository $adjudicacionRepository,
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Argument(
            description: 'Convocatoria a procesar',
            name: 'convocatoria',
        )] int $convocatoria,
        #[Option(
            description: 'Muestra información adicional sobre la convocatoria',
            name: 'info',
            shortcut: 'i',
        )] bool $info = false,
    ): int {
        $io->title('ADJUDICACIONES: ' . $convocatoria);

        $pdfPath = $this->getPdfPathAdjudicaciones($convocatoria);

        if (!$this->fileUtilitiesService->fileExists($pdfPath)) {
            $io->writeln('<error>Archivo PDF no encontrado.</error>');
            return Command::FAILURE;
        }

        $adjudicacionesPasadas = $this->adjudicacionRepository->findByConvocatoria($convocatoria);
        if (!empty($adjudicacionesPasadas)) {
            $io->note(
                [
                    "Ya existen adjudicaciones para esta convocatoria.",
                    "Elimínelas primero con sipri:del $convocatoria --adjudicaciones"
                ]
            );
            return Command::FAILURE;
        }

        $adjudicaciones = $this->procesarAdjudicaciones($convocatoria, $pdfPath);


        $progressBar = new ProgressBar($io, sizeof($adjudicaciones));
        if (!$info) {
            $progressBar->start();
        }

        $omitidas = 0;
        $nuevas = 0;
        $noEncontradas = [];
        $ocep = 0;

        foreach ($adjudicaciones as $index => $adjudicaciones_array) {
            $plaza = null;
            $plazasObjetivo = $this->getPlazasObjetivoIfExists($convocatoria, $adjudicaciones_array);


            // CASE: No encuentra plazas candidatas. Quizás son OCEP o es que no las encuentra...

            if (empty($plazasObjetivo)) {
                if ($this->isPlazaOCEP($adjudicaciones_array)) {
                    $ocep++;
                }

                if (!$this->isPlazaOCEP($adjudicaciones_array)) {
                    $noEncontradas[] = $adjudicaciones_array;
                    if ($info) {
                        $io->writeln(
                            '<error>No se ha encontrado ninguna plaza para los criterios especificados.</error>'
                        );
                        $io->writeln('<info>Criterios: ' . json_encode($adjudicaciones_array) . '</info>');
                    }
                }
                continue;
            }

            $plaza = $this->decidirPlazaObjetivo($plazasObjetivo);

            if (!$plaza) {
                continue;
            }

            if (!$plaza->adjudicadaCompletamente()) {
                $plaza->addAdjudicacion(
                    new Adjudicacion(
                        id: null,
                        puesto: intval($adjudicaciones_array['orden']),
                        plaza: $plaza,
                    )
                );
                $nuevas++;
            } else {
                $omitidas++;
            }

            $this->plazaRepository->save($plaza, clear: true);

            if (!$info) {
                $progressBar->advance();
            }
        }


        if (!$info) {
            $progressBar->clear();
        }

        if (!empty($noEncontradas)) {
            $io->writeln('<error>Algunas adjudicaciones no se han podido asociar a plazas.</error>');
            foreach ($noEncontradas as $noEncontrada) {
                $io->writeln(json_encode($noEncontrada));
            }
        }

        $io->table(['Resultado', 'Valor'], [
            ['Convocatoria', $convocatoria],
            ['Adjudicaciones procesadas', count($adjudicaciones)],
            ['Adjudicaciones OCEP', $ocep],
            ['Adjudicaciones persistidas en DB', $nuevas],
            ['Adjudicaciones omitidas', $omitidas],
            ['Adjudicaciones no asociadas a plazas', count($noEncontradas)],
        ]);

        return Command::SUCCESS;
    }


    /**
     * @param int $convocatoria
     * @param mixed $adjudicaciones_array
     * @return Plaza[]|null
     */
    public function getPlazasObjetivoIfExists(int $convocatoria, mixed $adjudicaciones_array): ?array
    {
        return $this->plazaRepository->findByAttributes(
            convocatoriaId: $convocatoria,
            centroId: $adjudicaciones_array['centro'],
            especialidadId: $adjudicaciones_array['puesto'],
            tipo: TipoPlazaEnum::fromString($adjudicaciones_array['tipo']),
            //obligatoriedad: ObligatoriedadPlazaEnum::fromString($adjudicaciones_array['voluntaria']),
            fechaPrevistaCese: $adjudicaciones_array['fecha_prevista_cese'],
        );
    }

    /**
     * @param int $convocatoria
     * @return string
     * @throws Exception
     */
    private function getPdfPathAdjudicaciones(int $convocatoria): string
    {
        $path = FileUtilitiesService::getLocalPathForConvocatoria($convocatoria);
        return $path . $convocatoria . '_adjudicados.pdf';
    }

    private function procesarAdjudicaciones(int $convocatoria, string $pdfPath): array
    {
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

        return $resultados;
    }

    private function isPlazaOCEP(mixed $adjudicaciones_array): bool
    {
        return in_array($adjudicaciones_array['centro'], Centro::OCEP_OTROS_CENTROS);
    }


    private function decidirPlazaObjetivo(array $plazasObjetivo): ?Plaza
    {
        if (count($plazasObjetivo) === 1) {
            return $plazasObjetivo[0];
        }

        if (count($plazasObjetivo) > 1) {
            foreach ($plazasObjetivo as $plazaComprueba) {
                $plaza = $plazaComprueba;
                if (!$plazaComprueba->adjudicadaCompletamente()) {
                    return $plaza;
                }
            }
        }
        return null;
    }

}
