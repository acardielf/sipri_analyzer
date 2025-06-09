<?php

namespace App\Command;

use App\Service\FileUtilitiesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'sipri:get-convocatoria',
    description: 'Get convocatoria from SIPRI',
)]
class GetConvocatoria extends Command
{
    public function __construct(
        private readonly HttpClientInterface  $httpClient,
        private readonly FileUtilitiesService $fileUtilitiesService,
    )
    {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this->setHelp('This command allows you to get convocatoria from SIPRI');
        $this->addArgument('convocatoria', InputArgument::REQUIRED, 'Convocatoria to download');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $convocatoria = $input->getArgument('convocatoria');
        $convocatoria = intval($convocatoria);

        $output->writeln('Convocatoria solicited: ' . $convocatoria);

        $url = 'https://www.juntadeandalucia.es/educacion/sipri/normativa/descarga/' . $convocatoria . '/C/2';
        $localPath = 'pdfs/' . $convocatoria . '/';
        $file = 'plazas.pdf';

        $this->fileUtilitiesService->createDirectoryIfNotExists('pdfs/' . $convocatoria);

        if ($this->fileUtilitiesService->fileExists($localPath . $file)){
            $output->writeln('El PDF ya existe localmente, no se descarga.');
            return Command::SUCCESS;
        }

        // Verificar si el PDF existe remotamente (HEAD)
        try {
            $response = $this->httpClient->request('HEAD', $url);
            if ($response->getStatusCode() !== 200) {
                $output->writeln("El archivo no existe o no es accesible (HTTP {$response->getStatusCode()}).");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $output->writeln('Error al comprobar el archivo remoto: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Descargar el archivo
        try {
            $response = $this->httpClient->request('GET', $url);
            $this->fileUtilitiesService->saveContentToFile($localPath . $file, $response->getContent());
            $output->writeln("PDF descargado en: $localPath");
        } catch (\Exception $e) {
            $output->writeln('Error al descargar el archivo: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
