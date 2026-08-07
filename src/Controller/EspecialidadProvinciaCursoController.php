<?php

namespace App\Controller;

use App\Entity\Adjudicacion;
use App\Entity\Plaza;
use App\Enum\TipoPlazaEnum;
use App\Repository\CursoRepository;
use App\Repository\EspecialidadRepository;
use App\Repository\PlazaRepository;
use App\Repository\ProvinciaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EspecialidadProvinciaCursoController extends AbstractController
{

    public function __construct(
        private readonly CursoRepository $cursoRepository,
        private readonly ProvinciaRepository $provinciaRepository,
        private readonly PlazaRepository $plazaRepository,
        private readonly EspecialidadRepository $especialidadRepository,
    ) {
    }

    /**
     * Todas las adjudicaciones del curso, de todas las provincias, por fecha.
     */
    #[Route(
        '/especialidad/{especialidad}/{curso}/',
        name: 'app_especialidad_curso',
        requirements: ['curso' => '\d{4}']
    )]
    public function porCurso(string $especialidad, int $curso): Response
    {
        $curso = $this->cursoRepository->findOneBy(['id' => $curso]);
        $especialidad = $this->especialidadRepository->findOneBy(['id' => $especialidad]);

        if (!$curso) {
            throw $this->createNotFoundException('Curso not found');
        }

        if (!$especialidad) {
            throw $this->createNotFoundException('Especialidad not found');
        }

        $plazas = $this->plazaRepository->getEspecialidadesByCurso($curso, $especialidad);

        $sinCubrir = $this->plazaRepository->findSinCubrirPorPlaza($curso, $especialidad);

        return $this->render('especialidades/curso_todas.html.twig', [
            'curso' => $curso,
            'especialidad' => $especialidad,
            'provincias' => $this->provinciaRepository->findAll(),
            'plazas' => $plazas,
            'sinCubrir' => $sinCubrir,
            'stats' => $this->calcularStats($plazas, $sinCubrir),
        ]);
    }

    /**
     * Resumen de puestos: una fila de plaza puede ofertar varios (`numero`),
     * así que todo se cuenta en puestos, no en filas.
     *
     * @param array<Plaza> $plazas
     * @param array<int, int> $sinCubrir
     * @return array<string, int>
     */
    private function calcularStats(array $plazas, array $sinCubrir): array
    {
        $stats = [
            'plazas' => 0,
            'vacantes' => 0,
            'vacantesInicio' => 0,
            'vacantesDesiertas' => 0,
            'vacantesAdjudicadas' => 0,
            'sustituciones' => 0,
            'sustitucionesDesiertas' => 0,
            'sustitucionesAdjudicadas' => 0,
            'adjudicadas' => 0,
            'desiertas' => array_sum($sinCubrir),
            'minOrden' => $this->encontrarOrdenMinimo($plazas) ?? 0,
            'maxOrden' => $this->encontrarOrdenMaximo($plazas) ?? 0,
        ];

        foreach ($plazas as $plaza) {
            $puestos = $plaza->getNumero();
            $desiertas = $sinCubrir[$plaza->getId()] ?? 0;
            $adjudicadas = $plaza->getAdjudicaciones()->count();

            $stats['plazas'] += $puestos;
            $stats['adjudicadas'] += $adjudicadas;

            if ($plaza->getTipo() === TipoPlazaEnum::VACANTE) {
                $stats['vacantes'] += $puestos;
                $stats['vacantesDesiertas'] += $desiertas;
                $stats['vacantesAdjudicadas'] += $adjudicadas;

                // Las vacantes de inicio de curso son las de las convocatorias de septiembre
                if ($plaza->getConvocatoria()->getFecha()?->format('m') === '09') {
                    $stats['vacantesInicio'] += $puestos;
                }
            } else {
                $stats['sustituciones'] += $puestos;
                $stats['sustitucionesDesiertas'] += $desiertas;
                $stats['sustitucionesAdjudicadas'] += $adjudicadas;
            }
        }

        return $stats;
    }

    private function encontrarOrdenMaximo(array $plazas): ?int
    {
        return array_reduce(
            $plazas,
            function (?int $ordenMaximo, Plaza $plaza) {
                $ordenesAdjudicaciones = $plaza->getAdjudicaciones()
                    ->map(fn(Adjudicacion $adjudicacion) => $adjudicacion->getOrden())
                    ->filter(fn($orden) => $orden !== null);

                if ($ordenesAdjudicaciones->isEmpty()) {
                    return $ordenMaximo;
                }

                $ordenMaximoActual = max($ordenesAdjudicaciones->toArray());
                return $ordenMaximo === null ? $ordenMaximoActual : max($ordenMaximo, $ordenMaximoActual);
            },
            null
        );
    }

    private function encontrarOrdenMinimo(array $plazas): ?int
    {
        return array_reduce(
            $plazas,
            function (?int $ordenMinimo, Plaza $plaza) {
                $ordenesAdjudicaciones = $plaza->getAdjudicaciones()
                    ->map(fn(Adjudicacion $adjudicacion) => $adjudicacion->getOrden())
                    ->filter(fn($orden) => $orden !== null);

                if ($ordenesAdjudicaciones->isEmpty()) {
                    return $ordenMinimo;
                }

                $ordenMinimoActual = min($ordenesAdjudicaciones->toArray());
                return $ordenMinimo === null ? $ordenMinimoActual : min($ordenMinimo, $ordenMinimoActual);
            },
            null
        );
    }



}
