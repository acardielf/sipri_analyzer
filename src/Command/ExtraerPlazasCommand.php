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
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'sipri:extraer-plazas',
    description: 'Extrae plazas desde un PDF y las guarda en formato JSON'
)]
readonly class ExtraerPlazasCommand
{
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

        $path = FileUtilitiesService::getLocalPathForConvocatoria($convocatoria);
        $pdfPath = $path . $convocatoria . '_plazas.pdf';
        $outputPath = $path . $convocatoria . '_plazas.json';

        if (!$this->fileUtilitiesService->fileExists($pdfPath)) {
            $io->writeln('<error>Archivo PDF no encontrado.</error>');
            return Command::FAILURE;
        }

        $parser = new Parser();
        $pdf = $parser->parseContent($this->fileUtilitiesService->getFileContent($pdfPath));
        $text = $pdf->getText();

        $fechaConvocatoria = $this->scrapperService->extractDateTimeFromText($text);


        $json = $this->tabulaService->generateJsonFromPdf(
            TipoProcesoEnum::PLAZA,
            $convocatoria,
            $pdfPath,
        );


        $resultados = [];

        foreach ($json as $numero => $contenido) {
            $resultadosPagina = $this->scrapperService->extractPlazasFromPageContent(
                $numero,
                $contenido,
                $convocatoria
            );
            $resultados = array_merge($resultados, $resultadosPagina);
        }

        $ocurrencia = 1;
        $nuevas = 0;

        $progressBar = new ProgressBar($io, sizeof($resultados));

        if (!$info) {
            $progressBar->start();
        }

        foreach ($resultados as $plaza_array) {
            $plazaDto = new PlazaDto(
                id: null,
                convocatoria: ConvocatoriaDto::fromId($convocatoria, $fechaConvocatoria),
                centro: CentroDto::fromString(
                    $plaza_array['centro'],
                    $plaza_array['localidad'],
                    $plaza_array['provincia']
                ),
                especialidad: EspecialidadDto::fromString($plaza_array['puesto']),
                tipoPlaza: TipoPlazaEnum::fromString($plaza_array['tipo']),
                obligatoriedadPlaza: ObligatoriedadPlazaEnum::fromString($plaza_array['voluntaria']),
                fechaPrevistaCese: $plaza_array['fecha_prevista_cese'] == "" ? null : DateTimeImmutable::createFromFormat(
                    'd/m/y',
                    $plaza_array['fecha_prevista_cese']
                ),
                numero: intval($plaza_array['num_plazas'])
            );

            $plaza = $this->plazaDtoToEntity->get($plazaDto, $ocurrencia);
            $texto = $plaza->getId() !== null ? '<comment>Plaza ya existe:</comment> ' : '<info>Plaza añadida:</info> ';

            if ($plaza->getId() == null) {
                $this->plazaRepository->save($plaza, clear: true);
                $nuevas++;
            }

            if ($info) {
                $io->writeln($texto . ' ' . $plazaDto->centro->nombre . ' - ' . $plazaDto->especialidad->nombre);
            } else {
                $progressBar->advance();
            }

            $ocurrencia++;
        }

        if (!$info) {
            $progressBar->clear();
        }

        file_put_contents($outputPath, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $io->table(['Resultado', 'Valor'], [
            ['Número de convocatoria', $convocatoria],
            ['Fecha de convocatoria', $fechaConvocatoria->format('d/m/Y')],
            ['Plazas extraídas', count($resultados)],
            ['Plazas persistidas en DB', $nuevas],
            ['Plazas omitidas', $ocurrencia - 1 - $nuevas],
            ['Total plazas', $ocurrencia - 1],
        ]);


        return Command::SUCCESS;
    }


}
