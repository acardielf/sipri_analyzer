<?php

namespace App\Command;

use App\Repository\AdjudicacionRepository;
use App\Repository\ConvocatoriaRepository;
use App\Repository\PlazaRepository;
use App\Service\FileUtilitiesService;
use Exception;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'sipri:del',
    description: 'Elimina una convocatoria, sus plazas y archivos asociados',
)]
readonly class RemoveConvocatoriaCommand
{
    public function __construct(
        private FileUtilitiesService $fileUtilitiesService,
        private ConvocatoriaRepository $convocatoriaRepository,
        private PlazaRepository $plazaRepository,
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
            description: 'Elimina también los archivos asociados a la convocatoria.',
            name: 'files',
            shortcut: 'f',
        )] bool $removeFiles = false,
        #[Option(
            description: 'Elimina las adjudicaciones asociadas a la convocatoria',
            name: 'adjudicaciones',
            shortcut: 'a',
        )] bool $adjudicaciones = false,
    ): int {
        {
            $io->title('ELIMINAR CONVOCATORIA: ' . $convocatoria);

            $io->table(['Opciones', 'Valor'], [
                ['Eliminar ficheros', $removeFiles ? 'Sí' : 'No'],
                ['Modo', $adjudicaciones ? 'Adjudicaciones' : 'Plazas y Convocatoria'],
            ]);

            $path = FileUtilitiesService::getLocalPathForConvocatoria($convocatoria);

            if ($removeFiles) {
                $this->removeFiles($path, $convocatoria, $io);
            }

            if ($adjudicaciones) {
                $this->removeOnlyAdjudicaciones($convocatoria, $io);
            }

            if (!$adjudicaciones) {
                $this->removePlazasAndConvocatoria($convocatoria, $io);
            }

            return Command::SUCCESS;
        }
    }

    private function removePlazasAndConvocatoria(int $convocatoria, SymfonyStyle $io): int
    {
        $convocatoriaEntity = $this->convocatoriaRepository->find($convocatoria);

        if (!$convocatoriaEntity) {
            $io->note('Convocatoria no encontrada.');
            return Command::FAILURE;
        }

        $numPlazas = count($convocatoriaEntity->getPlazas());
        $this->plazaRepository->removeAll($convocatoriaEntity->getPlazas());
        $this->convocatoriaRepository->remove($convocatoriaEntity);

        $io->table(['Resultado', 'Valor'], [
            ['Convocatoria eliminada', $convocatoria],
            ['Plazas eliminadas', $numPlazas],
        ]);
        return Command::SUCCESS;
    }

    private function removeOnlyAdjudicaciones(int $convocatoria, SymfonyStyle $io): void
    {
        $listaAdjudicaciones = $this->adjudicacionRepository->findByConvocatoria($convocatoria);
        $numAdjudicaciones = count($listaAdjudicaciones);
        $this->adjudicacionRepository->removeAll($listaAdjudicaciones);

        $io->table(['Resultado', 'Valor'], [
            ['Convocatoria tratada', $convocatoria],
            ['Adjudicaciones eliminadas', $numAdjudicaciones],
        ]);
    }

    /**
     * @param string $path
     * @param int $convocatoria
     * @param SymfonyStyle $io
     * @return void
     */
    public function removeFiles(string $path, int $convocatoria, SymfonyStyle $io): void
    {
        $pdfPath = $path . $convocatoria . '_plazas.pdf';
        $outputPath = $path . $convocatoria . '_plazas.json';
        $adjudicadosPath = $path . $convocatoria . '_adjudicados.pdf';

        if ($this->fileUtilitiesService->fileExists($pdfPath)) {
            $this->fileUtilitiesService->removeFile($pdfPath);
            $io->comment("Archivo $pdfPath eliminado.");
        }

        if ($this->fileUtilitiesService->fileExists($outputPath)) {
            $this->fileUtilitiesService->removeFile($outputPath);
            $io->comment("Archivo $outputPath eliminado.");
        }

        if ($this->fileUtilitiesService->fileExists($adjudicadosPath)) {
            $this->fileUtilitiesService->removeFile($adjudicadosPath);
            $io->comment("Archivo $adjudicadosPath eliminado.");
        }
    }
}
