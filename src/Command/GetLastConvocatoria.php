<?php

namespace App\Command;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SessionCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;

#[AsCommand(
    name: 'sipri:last-convocatoria',
    description: 'Get last convocatoria from SIPRI',
)]
class GetLastConvocatoria extends Command
{

    private const string HISTORICO_URL = './historico/';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to get last convocatoria from SIPRI');
        $this->addOption(
            'all',
            'all',
            InputOption::VALUE_OPTIONAL,
            'Execute get/ext/adj for the last convocatoria',
            true
        );
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mainJar = new SessionCookieJar('MainSipriSession', true);

        $mainClient = new Client([
            'base_uri' => GetConvocatoria::BASE_URL,
            'cookies' => $mainJar,
            'headers' => [
                'User-Agent' => GetConvocatoria::CLIENT_AGENT,
            ],
        ]);

        $response = $mainClient->request('GET', self::HISTORICO_URL);

        $crawler = new Crawler($response->getBody()->getContents());

        $firstOption = $crawler->filter('#convocatoria option')->first();
        $convocatoria = (int)$firstOption->attr('value');

        $output->writeln('Última convocatoria detectada: ' . $convocatoria);

        $io = new SymfonyStyle($input, $output);

        $order = [
            'sipri:get',
            'sipri:ext',
            'sipri:adj',
        ];

        foreach ($order as $lineCommand) {
            $io->writeln("Ejecutando comando $lineCommand");
            $command = $this->getApplication()->find($lineCommand);

            $arguments = [
                'command' => $lineCommand,
                'convocatoria' => $convocatoria,
            ];

            $gInput = new ArrayInput($arguments);

            $returnCode = $command->run($gInput, $output);

            if ($returnCode !== Command::SUCCESS) {
                $io->error("El subcomando $lineCommand falló.");
                return Command::FAILURE;
            }
        }

        $io->writeln('Limpiando ejecución...');

        $command = $this->getApplication()->find('sipri:del');

        $arguments = [
            'command' => 'sipri:del',
            'convocatoria' => $convocatoria,
            '--exclusively-files' => true,
            '--files' => true,
        ];

        $gInput = new ArrayInput($arguments);

        $returnCode = $command->run($gInput, $output);

        if ($returnCode !== Command::SUCCESS) {
            $io->error('El subcomando de eliminación falló.');
            return Command::FAILURE;
        }

        $io->success('Comando secundario ejecutado correctamente.');
        return Command::SUCCESS;
    }

}
