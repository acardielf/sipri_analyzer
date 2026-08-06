<?php

namespace App\Controller;

use App\Repository\ConvocatoriaRepository;
use App\Repository\PlazaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConvocatoriaDetalleController extends AbstractController
{
    public function __construct(
        private readonly ConvocatoriaRepository $convocatoriaRepository,
        private readonly PlazaRepository $plazaRepository,
    ) {
    }

    #[Route('/convocatoria/{id}/', name: 'app_convocatoria_detalle')]
    public function index(string $id): Response
    {
        $convocatoria = $this->convocatoriaRepository->find($id);

        if (!$convocatoria) {
            throw $this->createNotFoundException('Convocatoria no encontrada');
        }

        $plazas = $this->plazaRepository->findByConvocatoriaArray($id);

        $plazasByProv = [];
        $especialidades = [];

        // Una convocatoria de septiembre publica las vacantes de inicio de curso
        $esInicioCurso = $convocatoria->getFecha()?->format('m') === '09';

        // Sin adjudicaciones extraídas no se puede saber qué quedó sin cubrir
        $tieneAdjudicaciones = false;
        foreach ($plazas as $p) {
            if ((int)$p['adjCount'] > 0) {
                $tieneAdjudicaciones = true;
                break;
            }
        }

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
            'desiertas' => 0,
        ];

        $sinCubrirPorPlaza = [];

        foreach ($plazas as $p) {
            $plazasByProv[$p['provNombre']][] = $p;
            $especialidades[$p['espId']] = $p['espNombre'];

            $tipoValue = $p['tipo'] instanceof \App\Enum\TipoPlazaEnum ? $p['tipo']->value : $p['tipo'];
            $puestos = (int)$p['numero'];
            $adjudicadas = (int)$p['adjCount'];
            $sinCubrir = $tieneAdjudicaciones ? max(0, $puestos - $adjudicadas) : 0;
            $sinCubrirPorPlaza[$p['id']] = $sinCubrir;

            $stats['plazas'] += $puestos;
            $stats['adjudicadas'] += $adjudicadas;
            $stats['desiertas'] += $sinCubrir;

            if ($tipoValue === 'VACANTE') {
                $stats['vacantes'] += $puestos;
                $stats['vacantesAdjudicadas'] += $adjudicadas;
                $stats['vacantesDesiertas'] += $sinCubrir;
                if ($esInicioCurso) {
                    $stats['vacantesInicio'] += $puestos;
                }
            } else {
                $stats['sustituciones'] += $puestos;
                $stats['sustitucionesAdjudicadas'] += $adjudicadas;
                $stats['sustitucionesDesiertas'] += $sinCubrir;
            }
        }

        ksort($plazasByProv);
        asort($especialidades);

        return $this->render('convocatoria/detalle.html.twig', [
            'convocatoria'  => $convocatoria,
            'plazasByProv'  => $plazasByProv,
            'especialidades' => $especialidades,
            'stats'         => $stats,
            'sinCubrir'     => $sinCubrirPorPlaza,
        ]);
    }
}
