<?php

namespace App\Command;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Genera las imágenes de previsualización (Open Graph) que muestran Telegram,
 * WhatsApp, Facebook y demás al compartir un enlace.
 *
 * Son ficheros estáticos en public/og/, así que el sitio publicado en GitHub
 * Pages no necesita ningún backend. Se generan una vez por sección, no por
 * página: el texto específico de cada URL ya viaja en las meta etiquetas.
 */
#[AsCommand(
    name: 'sipri:og-images',
    description: 'Genera las imágenes de previsualización para compartir en redes',
)]
class GenerarOgImagesCommand extends Command
{
    private const int ANCHO = 1200;
    private const int ALTO = 630;

    private const string VERDE = '#1a5f28';
    private const string VERDE_MEDIO = '#2e8636';
    private const string VERDE_CLARO = '#4aaa58';
    private const string ROJO = '#c41111';

    /** Una imagen por sección; la clave es el nombre del fichero. */
    private const array VARIANTES = [
        'default' => ['titulo' => 'Adjudicaciones docentes', 'sub' => 'Convocatorias del SIPRI en Andalucía'],
        'especialidad' => ['titulo' => 'Especialidades', 'sub' => 'Qué posiciones de la bolsa se han llamado, y cuándo'],
        'convocatoria' => ['titulo' => 'Convocatorias', 'sub' => 'Plazas ofertadas en cada adjudicación del SIPRI'],
        'centro' => ['titulo' => 'Centros', 'sub' => 'Plazas ofertadas centro por centro'],
        'cuerpo' => ['titulo' => 'Cuerpos docentes', 'sub' => 'Secundaria, Maestros, FP, Idiomas, Música y Artes'],
        'faq' => ['titulo' => 'Preguntas frecuentes', 'sub' => 'De dónde salen los datos y cómo interpretarlos'],
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!\function_exists('imagecreatetruecolor')) {
            $io->error('Se necesita la extensión GD de PHP.');

            return Command::FAILURE;
        }

        $fuente = $this->localizarFuente();
        if ($fuente === null) {
            $io->error('No se ha encontrado ninguna fuente TrueType utilizable.');

            return Command::FAILURE;
        }

        $destino = $this->projectDir . '/public/og';
        if (!is_dir($destino) && !mkdir($destino, 0775, true) && !is_dir($destino)) {
            $io->error(sprintf('No se ha podido crear el directorio %s', $destino));

            return Command::FAILURE;
        }

        foreach (self::VARIANTES as $nombre => $textos) {
            $ruta = sprintf('%s/%s.png', $destino, $nombre);
            $this->generar($ruta, $textos['titulo'], $textos['sub'], $fuente);
            $io->writeln(sprintf(' <info>✓</info> %s.png (%s KB)', $nombre, round(filesize($ruta) / 1024)));
        }

        $io->success(sprintf('%d imágenes generadas en public/og/', count(self::VARIANTES)));

        return Command::SUCCESS;
    }

    private function generar(string $ruta, string $titulo, string $subtitulo, string $fuente): void
    {
        $img = imagecreatetruecolor(self::ANCHO, self::ALTO);

        $verde = $this->color($img, self::VERDE);
        $verdeMedio = $this->color($img, self::VERDE_MEDIO);
        $verdeClaro = $this->color($img, self::VERDE_CLARO);
        $rojo = $this->color($img, self::ROJO);
        $blanco = $this->color($img, '#ffffff');
        $tenue = $this->color($img, '#a9cdb0');

        imagefilledrectangle($img, 0, 0, self::ANCHO, self::ALTO, $verde);

        // Degradado sutil hacia el verde medio en la mitad inferior
        for ($y = (int)(self::ALTO * .45); $y < self::ALTO; $y++) {
            $mezcla = ($y - self::ALTO * .45) / (self::ALTO * .55);
            imageline(
                $img,
                0,
                $y,
                self::ANCHO,
                $y,
                $this->mezclar($img, self::VERDE, self::VERDE_MEDIO, $mezcla * .55)
            );
        }

        // Las barras del favicon a gran tamaño, todas apoyadas en la misma base
        $bx = 90;
        $base = 210;
        foreach ([[0, 96, $blanco], [46, 138, $blanco], [92, 80, $blanco], [138, 46, $rojo]] as [$dx, $alto, $tono]) {
            imagefilledrectangle($img, $bx + $dx, $base - $alto, $bx + $dx + 32, $base, $tono);
        }

        imagettftext($img, 30, 0, $bx + 205, $base - 2, $blanco, $fuente, 'Analizador SIPRI');

        // Título de la sección, partido en líneas si no cabe
        $y = 360;
        foreach ($this->ajustar($titulo, $fuente, 62, self::ANCHO - 180) as $linea) {
            imagettftext($img, 62, 0, 90, $y, $blanco, $fuente, $linea);
            $y += 82;
        }

        imagefilledrectangle($img, 90, $y + 6, 190, $y + 12, $verdeClaro);
        $y += 66;

        foreach ($this->ajustar($subtitulo, $fuente, 27, self::ANCHO - 180) as $linea) {
            imagettftext($img, 27, 0, 90, $y, $tenue, $fuente, $linea);
            $y += 40;
        }

        imagettftext($img, 22, 0, 90, self::ALTO - 52, $verdeClaro, $fuente, '¿Cuándo voy a currar?');

        imagepng($img, $ruta, 9);
        imagedestroy($img);
    }

    /**
     * Parte un texto en líneas que quepan en el ancho indicado.
     *
     * @return array<string>
     */
    private function ajustar(string $texto, string $fuente, int $tam, int $ancho): array
    {
        $lineas = [];
        $actual = '';

        foreach (explode(' ', $texto) as $palabra) {
            $prueba = $actual === '' ? $palabra : $actual . ' ' . $palabra;
            $caja = imagettfbbox($tam, 0, $fuente, $prueba);

            if ($caja[2] - $caja[0] > $ancho && $actual !== '') {
                $lineas[] = $actual;
                $actual = $palabra;
                continue;
            }

            $actual = $prueba;
        }

        if ($actual !== '') {
            $lineas[] = $actual;
        }

        return $lineas;
    }

    private function color(\GdImage $img, string $hex): int
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return imagecolorallocate($img, $r, $g, $b);
    }

    private function mezclar(\GdImage $img, string $desde, string $hasta, float $factor): int
    {
        [$r1, $g1, $b1] = sscanf($desde, '#%02x%02x%02x');
        [$r2, $g2, $b2] = sscanf($hasta, '#%02x%02x%02x');

        return imagecolorallocate(
            $img,
            (int)round($r1 + ($r2 - $r1) * $factor),
            (int)round($g1 + ($g2 - $g1) * $factor),
            (int)round($b1 + ($b2 - $b1) * $factor),
        );
    }

    /**
     * Fuentes habituales en los contenedores Debian/Alpine y en el equipo local.
     */
    private function localizarFuente(): ?string
    {
        $candidatas = [
            '/usr/share/fonts/liberation-sans-fonts/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/TTF/LiberationSans-Bold.ttf',
            '/usr/share/fonts/dejavu-sans-fonts/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
        ];

        foreach ($candidatas as $ruta) {
            if (is_readable($ruta)) {
                return $ruta;
            }
        }

        $encontradas = glob('/usr/share/fonts/**/*-Bold.ttf', GLOB_BRACE) ?: [];

        return $encontradas[0] ?? null;
    }
}
