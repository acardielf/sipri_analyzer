<?php

namespace App\Command;

use App\Repository\ConvocatoriaRepository;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SessionCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
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

    /**
     * Tope del relleno de huecos. Con la base de datos al día no se alcanza
     * nunca; está para que una base de datos vacía o muy atrasada no dispare
     * sin querer un reproceso completo de cientos de convocatorias, que son
     * horas de tabula. Si el hueco es mayor que esto, lo suyo es mirar qué ha
     * pasado y lanzar un reproceso a mano.
     */
    private const int MAX_HUECOS = 20;

    public function __construct(
        private readonly ConvocatoriaRepository $convocatoriaRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('This command allows you to get last convocatoria from SIPRI');
        $this->addOption(
            name: 'back',
            shortcut: 'b',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Get last X convocatorias back',
            default: 0,
        );
        $this->addOption(
            name: 'rellenar',
            shortcut: 'r',
            mode: InputOption::VALUE_NEGATABLE,
            description: 'Empezar en la primera convocatoria que falte en la base de datos, no solo en las --back últimas',
            default: false,
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
        $lastConvocatoria = (int)$firstOption->attr('value');

        $output->writeln('Última convocatoria detectada: ' . $lastConvocatoria);

        $startingConvocatoria = $lastConvocatoria;
        $reduce = (int)$input->getOption('back');

        if ($reduce > 0) {
            $startingConvocatoria = $lastConvocatoria - $reduce;
        }

        // Una ventana fija de --back solo vale si la base de datos viene al
        // día. Si viene atrasada —el CI heredó un artefacto viejo, o un run se
        // quedó a medias— las convocatorias intermedias caen fuera de la
        // ventana y no las recupera nadie: hay que arrancar donde la base de
        // datos se quedó.
        if ($input->getOption('rellenar')) {
            $ultimoEnBd = $this->convocatoriaRepository->findUltimoIdNumerico();
            $primeraQueFalta = $ultimoEnBd + 1;
            $suelo = $lastConvocatoria - self::MAX_HUECOS;

            $output->writeln("Última convocatoria en la base de datos: $ultimoEnBd");

            if ($primeraQueFalta < $startingConvocatoria) {
                if ($primeraQueFalta < $suelo) {
                    $output->writeln(sprintf(
                        '<comment>El hueco (%d..%d) supera el tope de %d convocatorias: se arranca en la %d. '
                        . 'Revisa de dónde viene la base de datos.</comment>',
                        $primeraQueFalta,
                        $lastConvocatoria,
                        self::MAX_HUECOS,
                        $suelo,
                    ));
                    $primeraQueFalta = $suelo;
                }

                $output->writeln("Hay convocatorias sin procesar: se amplía la ventana hasta la $primeraQueFalta");
                $startingConvocatoria = $primeraQueFalta;
            }
        }

        $startingConvocatoria = max(1, $startingConvocatoria);

        $output->writeln("Iniciando desde convocatoria: $startingConvocatoria");

        $io = new SymfonyStyle($input, $output);

        $order = [
            'sipri:get',
            'sipri:ext',
            'sipri:adj',
        ];


        for ($currentConvocatoria = $startingConvocatoria; $currentConvocatoria <= $lastConvocatoria; $currentConvocatoria++) {
            foreach ($order as $lineCommand) {
                $io->writeln("Ejecutando comando $lineCommand");
                $command = $this->getApplication()->find($lineCommand);

                $arguments = [
                    'command' => $lineCommand,
                    'convocatoria' => $currentConvocatoria,
                ];

                $gInput = new ArrayInput($arguments);

                $returnCode = $command->run($gInput, $output);

                if ($returnCode !== Command::SUCCESS) {
                    $io->error("El subcomando $lineCommand falló.");
                    return Command::FAILURE;
                }
            }
        }

        return Command::SUCCESS;
    }

}
