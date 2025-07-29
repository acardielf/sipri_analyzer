<?php

namespace App\Controller;

use App\Entity\Curso;
use App\Entity\Plaza;
use App\Entity\Provincia;
use App\Repository\CursoRepository;
use App\Repository\EspecialidadRepository;
use App\Repository\PlazaRepository;
use App\Repository\ProvinciaRepository;
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

        $chart = $this->createChart($chartBuilder, $cursos, $provincias, $result);

        return $this->render('especialidades/curso.html.twig', [
            'provincias' => $provincias,
            'cursos' => $cursos,
            'especialidad' => $especialidad,
            'plazasFiltradas' => $result,
            'chart' => $chart,
        ]);
    }

    /**
     * @param ChartBuilderInterface $chartBuilder
     * @param array<Curso> $cursos
     * @param array<Provincia> $provincias
     * @param array $result
     * @return Chart
     */
    private function createChart(
        ChartBuilderInterface $chartBuilder,
        array $cursos,
        array $provincias,
        array $result
    ): Chart {
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);


        $buildDataSet = $this->buildDataSet($provincias, $result);

        $chart->setData([
            'labels' => $this->getLabels($cursos),
            'datasets' => $buildDataSet,
        ]);

        $chart->setOptions([
            'plugins' => [
                'autocolors' => [
                    'enabled' => true,
                    'mode' => 'dataset',
                ],
            ],
        ]);

        return $chart;
    }

    private function getLabels(array $cursos): array
    {
        $labels = [];
        foreach ($cursos as $curso) {
            $labels[] = sprintf('%s', $curso->getNombre());
        }
        return $labels;
    }

    /**
     * @param array<Provincia> $provincias
     * @param array $result
     * @return array
     */
    private function buildDataSet(array $provincias, array $result): array
    {
        $data = [];
        $colors = [
            '#CB4335',
            '#1F618D',
            '#F1C40F',
            '#27AE60',
            '#884EA0',
            '#D35400',
            '#F39C12',
            '#16A085',
        ];

        $transpose = [];
        foreach ($result as $cursoId => $curso) {
            foreach ($curso as $provId => $totalPlazas) {
                $transpose[$provId][$cursoId] = $totalPlazas;
            }
        }

        $i = 0;
        foreach ($provincias as $provincia) {
            $data[] = [
                'label' => $provincia->getNombre(),
                'data' => array_values($transpose[$provincia->getId()] ?? []),
                'borderWidth' => 5,
                'backgroundColor' => $colors[$i],
                'borderColor' => $colors[$i],
            ];
            $i++;
        }

        return $data;
    }


}
