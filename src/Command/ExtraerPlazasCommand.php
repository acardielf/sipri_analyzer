<?php

namespace App\Command;

use App\Dto\CentroDto;
use App\Dto\ConvocatoriaDto;
use App\Dto\EspecialidadDto;
use App\Dto\PlazaDto;
use App\Enum\ObligatoriedadPlazaEnum;
use App\Enum\TipoPlazaEnum;
use App\Enum\TipoProcesoEnum;
use App\Repository\PlazaRepository;
use App\Service\DtoToEntity\PlazaDtoToEntity;
use App\Service\FileUtilitiesService;
use App\Service\ScrapperService;
use App\Service\TabulaPythonService;
use DateTimeImmutable;
use Exception;
use Smalot\PdfParser\Parser;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'sipri:extraer-plazas',
    description: 'Extrae plazas desde un PDF y las guarda en formato JSON'
)]
readonly class ExtraerPlazasCommand
{
    private const string DATE_FORMAT = 'd/m/y';
    private const int JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;

    public function __construct(
        private FileUtilitiesService $fileUtilitiesService,
        private ScrapperService $scrapperService,
        private PlazaDtoToEntity $plazaDtoToEntity,
        private PlazaRepository $plazaRepository,
        private TabulaPythonService $tabulaService,
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
        $io->title('CONVOCATORIA: ' . $convocatoria);

        $archivos = $this->prepararArchivos($convocatoria);

        if (!$this->fileUtilitiesService->fileExists($archivos['pdf'])) {
            $io->writeln('<error>Archivo PDF no encontrado.</error>');
            return Command::FAILURE;
        }

        $fechaConvocatoria = $this->extraerFechaConvocatoria($archivos['pdf']);
        $plazas = $this->procesarPlazas($convocatoria, $archivos['pdf']);
        $this->guardarResultadosJson($plazas, $archivos['output']);
        $resultadoProcesamiento = $this->persistirPlazas($plazas, $fechaConvocatoria, $convocatoria, $io, $info);

        $this->mostrarResumen($io, $convocatoria, $fechaConvocatoria, $resultadoProcesamiento);

        return Command::SUCCESS;
    }

    /**
     * @param int $convocatoria
     * @return array [
     *      'pdf' => string
     *      'output' => string
     * ]
     * @throws Exception
     */
    private function prepararArchivos(int $convocatoria): array
    {
        $path = FileUtilitiesService::getLocalPathForConvocatoria($convocatoria);
        return [
            'pdf' => $path . $convocatoria . '_plazas.pdf',
            'output' => $path . $convocatoria . '_plazas.json'
        ];
    }

    /**
     * @throws Exception
     */
    private function extraerFechaConvocatoria(string $pdfPath): DateTimeImmutable
    {
        $parser = new Parser();
        $pdf = $parser->parseContent($this->fileUtilitiesService->getFileContent($pdfPath));
        return $this->scrapperService->extractDateTimeFromText($pdf->getText());
    }

    private function procesarPlazas(int $convocatoria, string $pdfPath): array
    {
        $json = $this->tabulaService->generateJsonFromPdf(
            TipoProcesoEnum::PLAZA,
            $convocatoria,
            $pdfPath
        );

        $plazasProcesadas = [];
        foreach ($json as $numero => $contenido) {
            $plazasPagina = $this->scrapperService->extractPlazasFromPageContent(
                $numero,
                $contenido,
                $convocatoria
            );
            $plazasProcesadas = array_merge($plazasProcesadas, $plazasPagina);
        }

        return $plazasProcesadas;
    }

    /**
     * @throws Exception
     */
    private function persistirPlazas(
        array $plazas,
        DateTimeImmutable $fechaConvocatoria,
        int $convocatoria,
        SymfonyStyle $io,
        bool $info
    ): array {
        $progressBar = new ProgressBar($io, count($plazas));

        if (!$info) {
            $progressBar->start();
        }

        $ocurrencia = 1;
        $toInsert = [];
        $omitidas = [];

        foreach ($plazas as $plazaArray) {
            $plazaDto = $this->getDto($plazaArray, $convocatoria, $fechaConvocatoria);

            $plaza =
                $this->plazaRepository->findByHash($plazaDto, $ocurrencia) ??
                $this->plazaDtoToEntity->get($plazaDto, $ocurrencia);

            if ($plaza->getId() === null) {
                $toInsert[] = $plaza;
                $this->plazaRepository->save($plaza);
            } else {
                $omitidas[] = $plaza;
            }

            $this->mostrarProgreso($io, $info, $plaza, $plazaDto, $progressBar);
            $ocurrencia++;
        }

        if (!$info) {
            $progressBar->clear();
        }

        return [
            'total' => $ocurrencia - 1,
            'nuevas' => count($toInsert),
            'omitidas' => count($omitidas),
        ];
    }

    /**
     * @throws Exception
     */
    private function getDto(
        array $plazaArray,
        int $convocatoria,
        DateTimeImmutable $fechaConvocatoria
    ): PlazaDto {
        return new PlazaDto(
            id: null,
            convocatoria: ConvocatoriaDto::fromId($convocatoria, $fechaConvocatoria),
            centro: CentroDto::fromString(
                $plazaArray['centro'],
                $plazaArray['localidad'],
                $plazaArray['provincia']
            ),
            especialidad: EspecialidadDto::fromString($plazaArray['puesto']),
            tipoPlaza: TipoPlazaEnum::fromString($plazaArray['tipo']),
            obligatoriedadPlaza: ObligatoriedadPlazaEnum::fromString($plazaArray['voluntaria']),
            fechaPrevistaCese: $plazaArray['fecha_prevista_cese'] ?
                DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $plazaArray['fecha_prevista_cese']) :
                null,
            numero: intval($plazaArray['num_plazas']),
            pagina: $plazaArray['pagina'],
            fila: $plazaArray['fila'],
        );
    }

    private function mostrarProgreso(
        SymfonyStyle $io,
        bool $info,
        $plaza,
        PlazaDto $plazaDto,
        ProgressBar $progressBar
    ): void {
        if ($info) {
            $estado = $plaza->getId(
            ) !== null ? '<comment>Plaza ya existe:</comment> ' : '<info>Plaza añadida:</info> ';
            $io->writeln(
                implode(' ', [
                    $estado,
                    "(P:$plazaDto->pagina L:$plazaDto->fila)",
                    $plazaDto->centro->nombre,
                    "-",
                    $plazaDto->especialidad->nombre
                ]),
                OutputInterface::OUTPUT_PLAIN
            );
        } else {
            $progressBar->advance();
        }
    }

    private function guardarResultadosJson(array $resultados, string $outputPath): void
    {
        file_put_contents($outputPath, json_encode($resultados, self::JSON_FLAGS));
    }

    private function mostrarResumen(
        SymfonyStyle $io,
        int $convocatoria,
        DateTimeImmutable $fechaConvocatoria,
        array $resultadoProcesamiento
    ): void {
        $io->table(['Resultado', 'Valor'], [
            ['Número de convocatoria', $convocatoria],
            ['Fecha de convocatoria', $fechaConvocatoria->format('d/m/Y')],
            ['Plazas persistidas en DB', $resultadoProcesamiento['nuevas']],
            ['Plazas omitidas', $resultadoProcesamiento['omitidas']],
            ['Total plazas', $resultadoProcesamiento['total']],
        ]);
    }
}
