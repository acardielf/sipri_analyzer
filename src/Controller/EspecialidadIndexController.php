<?php

namespace App\Controller;

use App\Entity\Curso;
use App\Entity\Plaza;
use App\Entity\Provincia;
use App\Repository\CursoRepository;
use App\Repository\EspecialidadRepository;
use App\Repository\PlazaRepository;
use App\Repository\ProvinciaRepository;
use App\Service\ChartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class EspecialidadIndexController extends AbstractController
{

    public function __construct(
        private readonly CursoRepository $cursoRepository,
        private readonly ProvinciaRepository $provinciaRepository,
        private readonly PlazaRepository $plazaRepository,
        private readonly EspecialidadRepository $especialidadRepository,
        private readonly ChartService $chartService,
    ) {
    }

    #[Route('/especialidad/{especialidad}/', name: 'app_especialidad_index')]
    public function index(string $especialidad, ChartBuilderInterface $chartBuilder): Response
    {
        $especialidad = $this->especialidadRepository->findOneBy(['id' => $especialidad]);

        if (!$especialidad) {
            throw $this->createNotFoundException('Especialidad not found');
        }

        $provincias = $this->provinciaRepository->findAll();
        $cursos = $this->cursoRepository->findAll();

        $result = [];
        foreach ($cursos as $curso) {
            foreach ($provincias as $provincia) {
                $result[$curso->getId()][$provincia->getId()] = 0;
            }
        }

        $plazas = $this->plazaRepository->getEspecialidadAsArray($especialidad);
        foreach ($plazas as $r) {
            $cursoId = $r['cursoId'];
            $provId = $r['provId'];
            $result[$cursoId][$provId] = $r['totalPlazas'];
        }

        $chart = $this->chartService->createChartByEspecialidadPorProvincia($chartBuilder, $cursos, $provincias, $result);

        return $this->render('especialidades/curso.html.twig', [
            'provincias' => $provincias,
            'cursos' => $cursos,
            'especialidad' => $especialidad,
            'plazasFiltradas' => $result,
            'chart' => $chart,
        ]);
    }

}
