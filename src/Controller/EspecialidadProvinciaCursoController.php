<?php

namespace App\Controller;

use App\Entity\Adjudicacion;
use App\Entity\Plaza;
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

    #[Route('/especialidad/{especialidad}/{curso}/{provincia}', name: 'app_especialidad_detail')]
    public function index(string $especialidad, int $curso, int $provincia): Response
    {
        $curso = $this->cursoRepository->findOneBy(['id' => $curso]);
        $especialidad = $this->especialidadRepository->findOneBy(['id' => $especialidad]);
        $provincia = $this->provinciaRepository->findOneBy(['id' => $provincia]);

        if (!$curso) {
            throw $this->createNotFoundException('Curso not found');
        }

        if (!$especialidad) {
            throw $this->createNotFoundException('Especialidad not found');
        }

        if (!$provincia) {
            throw $this->createNotFoundException('Provincia not found');
        }

        $plazas = $this->plazaRepository->getEspecialidadesByCursoAndProvincia($curso, $especialidad, $provincia);

        $desiertas = $this->plazaRepository->findPlazasDesiertas($plazas);

        $vacantes = $this->plazaRepository->findVacantesByCursoEspecialidadAndProvincia(
            $curso,
            $especialidad,
            $provincia
        );

        $desiertasId = array_map(
            fn(Plaza $plaza) => $plaza->getId(),
            $desiertas
        );


        return $this->render('especialidades/detalle.html.twig', [
            'curso' => $curso,
            'especialidad' => $especialidad,
            'provincia' => $provincia,
            'plazas' => $plazas,
            'desiertas' => $desiertasId,
            'vacantes' => $vacantes,
            'minOrden' => $this->encontrarOrdenMinimo($plazas) ?? 0,
            'maxOrden' => $this->encontrarOrdenMaximo($plazas) ?? 0,
        ]);
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
