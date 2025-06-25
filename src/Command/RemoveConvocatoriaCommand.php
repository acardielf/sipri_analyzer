<?php

namespace App\Command;

use App\Dto\CentroDto;
use App\Dto\ConvocatoriaDto;
use App\Dto\EspecialidadDto;
use App\Dto\PlazaDto;
use App\Entity\Convocatoria;
use App\Enum\ObligatoriedadPlazaEnum;
use App\Enum\TipoPlazaEnum;
use App\Repository\ConvocatoriaRepository;
use App\Repository\PlazaRepository;
use App\Service\DtoToEntity\PlazaDtoToEntity;
use App\Service\FileUtilitiesService;
use App\Service\PlazasScrapperService;
use DateTimeImmutable;
use Exception;
use Smalot\PdfParser\Parser;
use Spatie\PdfToText\Pdf;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'sipri:eliminar-convocatoria',
    description: 'Elimina una convocatoria, sus plazas y archivos asociados',
)]
class RemoveConvocatoriaCommand extends Command
{
    public function __construct(
        private readonly FileUtilitiesService  $fileUtilitiesService,
        private readonly ConvocatoriaRepository $convocatoriaRepository,
        private readonly PlazaRepository       $plazaRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Este comando elimina una convocatoria, sus plazas y archivos asociados');
        $this->addArgument('convocatoria', InputArgument::REQUIRED, 'Convocatoria a procesar');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $convocatoria = $input->getArgument('convocatoria');
        $convocatoria = intval($convocatoria);

        $output->writeln('Eliminando convocatoria: ' . $convocatoria);

        $path = FileUtilitiesService::getLocalPathForConvocatoria($convocatoria);

        $pdfPath = $path . $convocatoria . '_plazas.pdf';
        $outputPath = $path . $convocatoria . '_plazas.json';
        $adjudicadosPath = $path . $convocatoria . '_adjudicados.pdf';

        if ($this->fileUtilitiesService->fileExists($pdfPath)) {
            $this->fileUtilitiesService->removeFile($pdfPath);
        }

        if ($this->fileUtilitiesService->fileExists($outputPath)) {
            $this->fileUtilitiesService->removeFile($outputPath);
        }

        if ($this->fileUtilitiesService->fileExists($adjudicadosPath)) {
            $this->fileUtilitiesService->removeFile($adjudicadosPath);
        }

        $convocatoriaEntity = $this->convocatoriaRepository->find($convocatoria);
        $plazas = $this->plazaRepository->findBy(['convocatoria' => $convocatoria]);

        if (!$plazas) {
            $output->writeln('<error>No se encontraron plazas asociadas.</error>');
        }

        foreach ($plazas as $plaza) {
            $this->plazaRepository->remove($plaza);
        }

        if ($convocatoriaEntity) {
            $this->convocatoriaRepository->remove($convocatoriaEntity);
        } else {
            $output->writeln('<error>Convocatoria no encontrada.</error>');
        }



        return Command::SUCCESS;
    }

}
