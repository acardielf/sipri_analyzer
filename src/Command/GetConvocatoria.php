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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

#[AsCommand(
    name: 'sipri:get-convocatoria',
    description: 'Get convocatoria from SIPRI',
)]
class GetConvocatoria extends Command
{

    public const string BASE_URL = 'https://www.juntadeandalucia.es/educacion/sipri/normativa/';
    public const string HISTORICO_BUSCAR_URL = './historicobuscar/';
    public const string CLIENT_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3';

    public function __construct(
        private readonly FileUtilitiesService $fileUtilitiesService,
    ) {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this->setHelp('This command allows you to get convocatoria from SIPRI');
        $this->addArgument('convocatoria', InputArgument::REQUIRED, 'Convocatoria to download');
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NEGATABLE,
            'Force download even if files already exist',
            false
        );
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


        if (!$input->getOption('force')) {
            $allFilesExist = $this->checkIfAllFilesExist(
                array_map(
                    fn($file) => $file['sink'],
                    $files
                )
            );

            if ($allFilesExist) {
                $output->writeln('<info>Todos los archivos ya existen. No se descargarán de nuevo.</info>');
                return Command::SUCCESS;
            }
        }

        $jar = new SessionCookieJar('SipriSession', true);

        $client = new Client([
            'base_uri' => self::BASE_URL,
            'cookies' => $jar,
            'headers' => [
                'User-Agent' => self::CLIENT_AGENT,
            ],
        ]);

        $response = $client->request('POST', self::HISTORICO_BUSCAR_URL, [
            'form_params' => [
                'convocatoria' => $convocatoria,
            ],
        ]);

        $crawler = new Crawler($response->getBody()->getContents());

        $files['plazas']['url'] = $crawler->filter('a')->reduce(function (Crawler $node) {
            return trim($node->text()) === 'Anexo II Convocatoria';
        });

        $files['adjudicados']['url'] = $crawler->filter('a')->reduce(function (Crawler $node) {
            return trim($node->text()) === 'Anexo I (Adjudicados Orden Alfabético)';
        });

        if ($files['plazas']['url']->count() === 0 || $files['adjudicados']['url']->count() === 0) {
            $output->writeln('<error>No se encontraron archivos para la convocatoria ' . $convocatoria . '</error>');
            return Command::FAILURE;
        }

        foreach ($files as $key => $file) {
            if ($this->fileUtilitiesService->fileExists($file['sink']) && $input->getOption('force') === false) {
                $output->writeln("<info>Archivo PDF de $key ya existe. No se descargará de nuevo.</info>");
            } else {
                $url = parse_url($file['url']->attr('href'), PHP_URL_PATH);
                $url = str_replace('/historicobuscar', '', $url);
                $client->request('GET', $url, [
                    'cookies' => $jar,
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
