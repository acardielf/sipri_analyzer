<?php

namespace App\Command;

use App\Service\FileUtilitiesService;
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $convocatoria = $input->getArgument('convocatoria');
        $convocatoria = intval($convocatoria);

        $output->writeln('Convocatoria solicited: ' . $convocatoria);

        $localPath = 'pdfs/' . $convocatoria . '/';
        $file_plazas = $convocatoria . '_plazas.pdf';
        $file_convocados = $convocatoria . '_convocados.pdf';

        $this->fileUtilitiesService->createDirectoryIfNotExists('pdfs/' . $convocatoria);

        if (
            $this->fileUtilitiesService->fileExists($localPath . $file_plazas) &&
            $this->fileUtilitiesService->fileExists($localPath . $file_convocados)
        ) {
            $output->writeln('<info>Archivo PDF ya existe. No se descargará de nuevo.</info>');
            return Command::SUCCESS;
        }

        $this->jar = new SessionCookieJar('SipriSession', true);

        $this->client = new Client([
            'base_uri' => 'https://www.juntadeandalucia.es/',
            'cookies' => $this->jar
        ]);

        $this->client->request('POST', '/educacion/sipri/normativa/historicobuscar/', [
            'form_params' => [
                'convocatoria' => $convocatoria,
            ]
        ]);

        if ($this->fileUtilitiesService->fileExists($localPath . $file_plazas)) {
            $output->writeln('<info>Archivo PDF de PLAZAS ya existe. No se descargará de nuevo.</info>');
        } else {
            $this->client->request('GET', '/educacion/sipri/normativa/descarga/' . $convocatoria . '/C/2', [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'
                ],
                'cookies' => $this->jar,
                'sink' => $localPath . $file_plazas,
            ]);
        }

        if ($this->fileUtilitiesService->fileExists($localPath . $file_convocados)) {
            $output->writeln('<info>Archivo PDF de CONVOCADOS ya existe. No se descargará de nuevo.</info>');
        } else {
            $this->client->request('GET', '/educacion/sipri/normativa/descarga/' . $convocatoria . '/C/1', [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'
                ],
                'cookies' => $this->jar,
                'sink' => $localPath . $file_convocados,
            ]);
        }

        return Command::SUCCESS;
    }
}
