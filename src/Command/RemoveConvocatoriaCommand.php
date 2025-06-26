<?php

namespace App\Command;

use App\Repository\AdjudicacionRepository;
use App\Repository\ConvocatoriaRepository;
use App\Repository\PlazaRepository;
use App\Service\FileUtilitiesService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'sipri:del',
    description: 'Elimina una convocatoria, sus plazas y archivos asociados',
)]
class RemoveConvocatoriaCommand extends Command
{
    public function __construct(
        private readonly FileUtilitiesService $fileUtilitiesService,
        private readonly ConvocatoriaRepository $convocatoriaRepository,
        private readonly PlazaRepository $plazaRepository,
        private readonly AdjudicacionRepository $adjudicacionRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Este comando elimina una convocatoria, sus plazas y archivos asociados');
        $this->addArgument('convocatoria', InputArgument::REQUIRED, 'Convocatoria a procesar');
        $this->addOption(
            'adjudicaciones',
            'a',
            InputOption::VALUE_NEGATABLE,
            'Elimina las adjudicaciones asociadas a la convocatoria'
        );
        $this->addOption(
            'full',
            'f',
            InputOption::VALUE_NEGATABLE,
            'Elimina también los archivos asociados a la convocatoria'
        );
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

        if ($input->getOption('full')) {
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
            $output->writeln('<info>Archivos PDF eliminados.</info>');
        }

        if ($input->getOption('adjudicaciones')) {
            $adjudicaciones = $this->adjudicacionRepository->findByConvocatoria($convocatoria);
            $this->adjudicacionRepository->removeAll($adjudicaciones);
            $output->writeln('<info>Adjudicaciones eliminadas.</info>');
            return Command::SUCCESS;
        }

        $convocatoriaEntity = $this->convocatoriaRepository->find($convocatoria);
        $plazas = $this->plazaRepository->findBy(['convocatoria' => $convocatoria]);

        foreach ($plazas as $plaza) {
            $this->plazaRepository->remove($plaza);
        }

        if (!$plazas) {
            $output->writeln('<error>No se encontraron plazas asociadas.</error>');
        } else {
            $output->writeln('<info>Eliminadas ' . count($plazas) . ' plazas asociadas.</info>');
        }

        if ($convocatoriaEntity) {
            $this->convocatoriaRepository->remove($convocatoriaEntity);
            $output->writeln('<info>Convocatoria eliminada.</info>');
        } else {
            $output->writeln('<error>Convocatoria no encontrada.</error>');
        }

        return Command::SUCCESS;
    }

}
