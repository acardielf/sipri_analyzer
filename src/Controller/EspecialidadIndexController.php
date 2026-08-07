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
    /** Número de cursos visibles sin desplegar: el actual y los cuatro anteriores. */
    private const int COLUMNAS_VISIBLES = 5;

    private const array METRICAS_VACIAS = [
        'plazas' => 0,
        'plazasOfertadas' => 0,
        'vacantes' => 0,
        'vacantesInicio' => 0,
        'sustituciones' => 0,
        'desiertas' => 0,
        'minOrden' => 0,
        'maxOrden' => 0,
    ];

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
        $cursosConDatos = $this->cursoRepository->findAllDescent();
        $cursos = $this->cursoRepository->findAllParaTabla();

        $result = [];

        foreach ($cursos as $curso) {
            foreach ([...array_map(fn($p) => $p->getId(), $provincias), 'ALL'] as $ambito) {
                $result[$curso->getId()][$ambito] = self::METRICAS_VACIAS;
            }
        }

        $plazas = $this->plazaRepository->getEspecialidadAsArray($especialidad);

        foreach ($plazas as $r) {
            $cursoId = $r['cursoId'];
            $provId = $r['provId'];

            if (!isset($result[$cursoId])) {
                continue;
            }

            $result[$cursoId][$provId] = [
                'plazas' => $r['totalPlazas'],
                'plazasOfertadas' => $r['plazasOfertadas'],
                'vacantes' => $r['vacantes'],
                'vacantesInicio' => $r['vacantesInicio'],
                'sustituciones' => $r['sustituciones'],
                'desiertas' => $r['desiertas'],
                'minOrden' => $r['minOrden'],
                'maxOrden' => $r['maxOrden'],
            ];

            foreach (['plazas', 'plazasOfertadas', 'vacantes', 'vacantesInicio', 'sustituciones', 'desiertas'] as $metrica) {
                $result[$cursoId]['ALL'][$metrica] += $result[$cursoId][$provId][$metrica];
            }

            $minOrdenTotal = $result[$cursoId]['ALL']['minOrden'];
            if ($r['minOrden'] > 0 && ($minOrdenTotal === 0 || $r['minOrden'] < $minOrdenTotal)) {
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


        // El gráfico ignora los cursos que aún no han empezado
        $cursosGrafico = array_filter(
            [...$cursosConDatos],
            fn(Curso $curso) => isset($result[$curso->getId()])
        );

        $chart = $this->chartService->createChartByEspecialidadPorProvincia(
            $chartBuilder,
            array_reverse($cursosGrafico),
            $provincias,
            $result
        );

        $isInactiva = ($result[$cursoLast->getId()]['ALL']['plazas'] ?? 0) === 0
            && ($previo1 === null || ($result[$previo1->getId()]['ALL']['plazas'] ?? 0) === 0);

        return $this->render('especialidades/curso.html.twig', [
            'provincias' => $provincias,
            'cursos' => $cursos,
            'columnasVisibles' => self::COLUMNAS_VISIBLES,
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
            'isInactiva' => $isInactiva,
        ]);
    }

    /**
     * Recorre una sola vez las adjudicaciones del curso y produce las dos
     * estructuras que consume el tab «¿Cuándo voy a currar?»:
     *
     *  - `tabla`: rejilla orden × provincia con un chip por adjudicación.
     *  - `timeline`: el mismo conjunto reducido a lo que necesitan la barra de
     *    recorrido y el calendario, que se dibujan en cliente: posición,
     *    convocatoria y provincia. Las fechas se repiten mucho (hasta 67
     *    convocatorias para 3.500 adjudicaciones), así que van en una tabla
     *    aparte y cada adjudicación guarda sólo su índice.
     *
     * @return array{tabla: array, timeline: array}
     */
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

        $convocatorias = [];
        $puntos = [];

        /** @var Adjudicacion $adjudicacion */
        foreach ($adjudicacionesByCourse as $adjudicacion) {
            $provincia = $adjudicacion->getPlaza()->getCentro()->getLocalidad()->getProvincia()->getId();
            $convocatoria = $adjudicacion->getPlaza()->getConvocatoria();
            $f = $convocatoria->getFecha();
            $fecha = $f->format('d/M/Y');
            $fechaMin = $f->format('d/m/y');
            $tipo = $adjudicacion->getPlaza()->getTipo()->getShortLabel();
            $orden = $adjudicacion->getOrden();
            $centro = $adjudicacion->getPlaza()->getCentro()->getNombre();


            $adjudicaciones[$orden][$provincia][$i]['fecha'] = $fecha;
            $adjudicaciones[$orden][$provincia][$i]['fechaMin'] = $fechaMin;
            $adjudicaciones[$orden][$provincia][$i]['tipo'] = $tipo;
            $adjudicaciones[$orden][$provincia][$i]['centro'] = $centro;

            $convocatoriaId = $convocatoria->getId();
            if (!isset($convocatorias[$convocatoriaId])) {
                $convocatorias[$convocatoriaId] = [
                    'idx' => count($convocatorias),
                    'iso' => $f->format('Y-m-d'),
                ];
            }

            $puntos[] = [
                (int) $orden,
                $convocatorias[$convocatoriaId]['idx'],
                (string) $provincia,
            ];

            $i++;
        }
        ksort($adjudicaciones);

        return [
            'tabla' => $adjudicaciones,
            'timeline' => [
                'convocatorias' => array_values(array_column($convocatorias, 'iso')),
                'adjudicaciones' => $puntos,
            ],
        ];
    }

}
