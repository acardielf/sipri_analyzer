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
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $convocatoria = $input->getArgument('convocatoria');
        $convocatoria = intval($convocatoria);

        $output->writeln('Convocatoria solicitada: ' . $convocatoria);

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

        $paginas = $this->plazasScrapperService->getPagesContentFromText($text);

        $resultados = [];


        foreach ($paginas as $numero => $contenido) {
            $resultadosPagina = $this->plazasScrapperService->extractPageContent($numero, $contenido, $convocatoria);
//            $this->fileUtilitiesService->saveContentToFile(
//                $path . 'plazas_pag_' . $numero . '.txt',
//                $paginas[$numero]
//            );
            $resultados = array_merge($resultados, $resultadosPagina);
        }

        $ocurrencia = 1;
        foreach ($resultados as $plaza_array) {

            $plazaDto = new PlazaDto(
                id: null,
                convocatoria: ConvocatoriaDto::fromId($convocatoria),
                centro: CentroDto::fromString($plaza_array['centro'], $plaza_array['localidad'], $plaza_array['provincia']),
                especialidad: EspecialidadDto::fromString($plaza_array['puesto']),
                tipoPlaza: TipoPlazaEnum::fromString($plaza_array['tipo']),
                obligatoriedadPlaza: ObligatoriedadPlazaEnum::fromString($plaza_array['voluntaria']),
                fechaPrevistaCese: $plaza_array['fecha_prevista_cese'] == "" ? null : DateTimeImmutable::createFromFormat('d/m/y', $plaza_array['fecha_prevista_cese']),
                numero: intval($plaza_array['num_plazas'])
            );

            if ($this->plazaDtoToEntity->getIfExists($plazaDto, $ocurrencia)) {
                $output->writeln('<comment>Plaza ya existe:</comment> ' . $plazaDto->centro->nombre . ' - ' . $plazaDto->especialidad->nombre);
            } else {
                $plaza = $this->plazaDtoToEntity->get($plazaDto, $ocurrencia);
                $this->plazaRepository->save($plaza);
                $output->writeln('<info>Plaza añadida:</info> ' . $plazaDto->centro->nombre . ' - ' . $plazaDto->especialidad->nombre);
            }

            $ocurrencia++;
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
