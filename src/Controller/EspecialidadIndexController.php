<?php

namespace App\Controller;

use App\Entity\Adjudicacion;
use App\Entity\Curso;
use App\Entity\Especialidad;
use App\Repository\AdjudicacionRepository;
use App\Repository\CursoRepository;
use App\Repository\EspecialidadRepository;
use App\Repository\PlazaRepository;
use App\Repository\ProvinciaRepository;
use App\Service\ChartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;

class EspecialidadIndexController extends AbstractController
{

    public function __construct(
        private readonly CursoRepository $cursoRepository,
        private readonly ProvinciaRepository $provinciaRepository,
        private readonly PlazaRepository $plazaRepository,
        private readonly EspecialidadRepository $especialidadRepository,
        private readonly ChartService $chartService,
        private readonly AdjudicacionRepository $adjudicacionRepository,
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
        $cursos = $this->cursoRepository->findAllDescent();

        $result = [];

        foreach ($cursos as $curso) {
            foreach ($provincias as $provincia) {
                $result[$curso->getId()][$provincia->getId()]['plazas'] = 0;
                $result[$curso->getId()][$provincia->getId()]['minOrden'] = 0;
                $result[$curso->getId()][$provincia->getId()]['maxOrden'] = 0;
                $result[$curso->getId()]['ALL']['plazas'] = 0;
                $result[$curso->getId()]['ALL']['minOrden'] = 99999999999;
                $result[$curso->getId()]['ALL']['maxOrden'] = 0;
            }
        }

        $plazas = $this->plazaRepository->getEspecialidadAsArray($especialidad);

        foreach ($plazas as $r) {
            $cursoId = $r['cursoId'];
            $provId = $r['provId'];
            $result[$cursoId][$provId]['plazas'] = $r['totalPlazas'];
            $result[$cursoId][$provId]['minOrden'] = $r['minOrden'];
            $result[$cursoId][$provId]['maxOrden'] = $r['maxOrden'];

            $result[$cursoId]['ALL']['plazas'] += $r['totalPlazas'];
            if ($r['minOrden'] < $result[$cursoId]['ALL']['minOrden']) {
                $result[$cursoId]['ALL']['minOrden'] = $r['minOrden'];
            }
            if ($r['maxOrden'] > $result[$cursoId]['ALL']['maxOrden']) {
                $result[$cursoId]['ALL']['maxOrden'] = $r['maxOrden'];
            }
        }

        $cursoLast = $this->cursoRepository->findLast();
        $previo1 = $this->cursoRepository->findPrevious($cursoLast);
        $previo2 = $this->cursoRepository->findPrevious($previo1);
        $previo3 = $this->cursoRepository->findPrevious($previo2);


        $adjudicaciones = $this->getAdjudicaciones($especialidad, $cursoLast);
        $adjudicaciones_previo1 = $this->getAdjudicaciones($especialidad, $previo1);
        $adjudicaciones_previo2 = $this->getAdjudicaciones($especialidad, $previo2);
        $adjudicaciones_previo3 = $this->getAdjudicaciones($especialidad, $previo3);


        $chart = $this->chartService->createChartByEspecialidadPorProvincia(
            $chartBuilder,
            $cursos,
            $provincias,
            $result
        );

        return $this->render('especialidades/curso.html.twig', [
            'provincias' => $provincias,
            'cursos' => $cursos,
            'especialidad' => $especialidad,
            'plazasFiltradas' => $result,
            'chart' => $chart,
            'cursoLast' => $cursoLast,
            'cursoPrevio1' => $previo1,
            'cursoPrevio2' => $previo2,
            'cursoPrevio3' => $previo3,
            'adjudicaciones' => $adjudicaciones,
            'adjudicaciones_previo1' => $adjudicaciones_previo1,
            'adjudicaciones_previo2' => $adjudicaciones_previo2,
            'adjudicaciones_previo3' => $adjudicaciones_previo3,
        ]);
    }

    private function getAdjudicaciones(Especialidad $especialidad, ?Curso $curso): array
    {
        if (!$curso) {
            throw $this->createNotFoundException('Curso not found');
        }

        $adjudicacionesByCourse = $this->adjudicacionRepository->findByEspecialidadAndCurso(
            $especialidad,
            $curso,
        );

        $i = 0;
        $adjudicaciones = [];

        /** @var Adjudicacion $adjudicacion */
        foreach ($adjudicacionesByCourse as $adjudicacion) {
            $provincia = $adjudicacion->getPlaza()->getCentro()->getLocalidad()->getProvincia()->getId();
            $f = $adjudicacion->getPlaza()->getConvocatoria()->getFecha();
            $fecha = $f->format('d/M/Y');
            $fechaMin = $f->format('d/m/y');
            $tipo = $adjudicacion->getPlaza()->getTipo()->getShortLabel();
            $orden = $adjudicacion->getOrden();


            $adjudicaciones[$orden][$provincia][$i]['fecha'] = $fecha;
            $adjudicaciones[$orden][$provincia][$i]['fechaMin'] = $fechaMin;
            $adjudicaciones[$orden][$provincia][$i]['tipo'] = $tipo;

            $i++;
        }
        ksort($adjudicaciones);

        return $adjudicaciones;
    }

}
