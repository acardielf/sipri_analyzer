<?php

namespace App\Command;

use App\Service\FileUtilitiesService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SessionCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'sipri:get-convocatoria',
    description: 'Get convocatoria from SIPRI',
)]
class GetConvocatoria extends Command
{
    private SessionCookieJar $jar;
    private Client $client;

    const BASE_URL = 'https://www.juntadeandalucia.es';
    const DOWNLOAD_URL = '/educacion/sipri/normativa/descarga/';
    const HISTORICO_BUSCAR_URL = '/educacion/sipri/normativa/historicobuscar/';

    public function __construct(
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

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $convocatoria = $input->getArgument('convocatoria');
        $convocatoria = intval($convocatoria);

        $output->writeln('Convocatoria solicitada: ' . $convocatoria);

        $localPath = FileUtilitiesService::getLocalPathForConvocatoria($convocatoria);
        $files = FileUtilitiesService::getFilesForConvocatoria($convocatoria);

        $this->fileUtilitiesService->createDirectoryIfNotExists($localPath);

        $allFilesExist = $this->checkIfAllFilesExist(array_map(
            fn($file) => $file['sink'],
            $files
        ));

        if ($allFilesExist) {
            $output->writeln('<info>Todos los archivos ya existen. No se descargarán de nuevo.</info>');
            return Command::SUCCESS;
        }

        $this->jar = new SessionCookieJar('SipriSession', true);

        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            'cookies' => $this->jar
        ]);

        $this->client->request('POST', self::HISTORICO_BUSCAR_URL, [
            'form_params' => [
                'convocatoria' => $convocatoria,
            ]
        ]);

        foreach ($files as $key => $file) {

            if ($this->fileUtilitiesService->fileExists($file['sink'])) {
                $output->writeln("<info>Archivo PDF de $key ya existe. No se descargará de nuevo.</info>");
            } else {
                $this->client->request('GET', self::DOWNLOAD_URL . $convocatoria . $file['url'], [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'
                    ],
                    'cookies' => $this->jar,
                    'sink' => $file['sink'],
                ]);
            }
        }

        return Command::SUCCESS;
    }


    private function checkIfAllFilesExist(array $files): bool
    {
        return array_all($files, fn($filePath) => $this->fileUtilitiesService->fileExists($filePath));
    }

}
