<?php

namespace App\Controller;

use App\Repository\ConvocatoriaRepository;
use App\Repository\CursoRepository;
use App\Service\ChartService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;

class ConvocatoriasStatsController extends AbstractController
{

    public function __construct(
        private readonly ConvocatoriaRepository $convocatoriaRepository,
        private readonly CursoRepository $cursoRepository,
        private readonly ChartService $chartService,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/convocatorias/stats/', name: 'app_convocatorias_stat')]
    public function __invoke(ChartBuilderInterface $chartBuilder): Response
    {
        $convocatorias = $this->convocatoriaRepository->findWithBasicDataInArray();

        $result = [];
        $nextYear = 2024;// Año previo a bisiesto para incluir el 29 de febrero
        $year = $nextYear - 1;  // Año actual para el que se generan las estadísticas

        $start = new DateTime("$year-09-01");
        $end = new DateTime("$nextYear-06-30");

        while ($start <= $end) {
            $key = $start->format('W'); // numero de semana
            $result[$key] = [];
            $start->modify('+1 day');
        }
        $index_weeks = array_Keys($result);

        foreach ($convocatorias as $convocatoria) {
            $id = $convocatoria['id'];
            $curso = $convocatoria['curso'];
            $fecha = $convocatoria['fecha']->format('W'); // Formato 'día-mes'
            $result[$fecha][$curso][$id]['id'] = $convocatoria['id'];
            $result[$fecha][$curso][$id]['fecha'] = $convocatoria['fecha']->format('d-m-Y');
            $result[$fecha][$curso][$id]['plazas'] = $convocatoria['plazas'];
            $result[$fecha][$curso][$id]['vacantes'] = $convocatoria['vacantes'];
        }

        $cursos = $this->cursoRepository->findAll();

        $chart = $this->chartService->createChartPlazasPorCursosGeneral($chartBuilder, $cursos, $result, $index_weeks);

        return $this->render('convocatorias/stats.html.twig', [
            'convocatorias' => $chart,
            'chart' => $chart,
        ]);
    }


}
