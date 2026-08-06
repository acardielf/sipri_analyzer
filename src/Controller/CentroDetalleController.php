<?php

namespace App\Controller;

use App\Repository\CentroRepository;
use App\Repository\ConvocatoriaRepository;
use App\Repository\PlazaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CentroDetalleController extends AbstractController
{
    public function __construct(
        private readonly CentroRepository $centroRepository,
        private readonly PlazaRepository $plazaRepository,
        private readonly ConvocatoriaRepository $convocatoriaRepository,
    ) {
    }

    #[Route('/centro/{id}/', name: 'app_centro_detalle')]
    public function index(string $id): Response
    {
        $centro = $this->centroRepository->find($id);

        if (!$centro) {
            throw $this->createNotFoundException('Centro no encontrado');
        }

        $plazas = $this->plazaRepository->findByCentroArray($id);

        // Sin adjudicaciones extraídas no se puede saber si una plaza quedó desierta
        $convocatoriasConAdjudicaciones = array_flip(
            $this->convocatoriaRepository->findIdsConAdjudicaciones()
        );

        $plazasByCurso = [];
        $sinCubrir = [];

        foreach ($plazas as $p) {
            $plazasByCurso[$p['cursoNombre']][] = $p;

            $sinCubrir[$p['id']] = isset($convocatoriasConAdjudicaciones[$p['convId']])
                ? max(0, (int)$p['numero'] - (int)$p['adjCount'])
                : 0;
        }
        krsort($plazasByCurso);

        return $this->render('centro/detalle.html.twig', [
            'centro' => $centro,
            'plazasByCurso' => $plazasByCurso,
            'sinCubrir' => $sinCubrir,
        ]);
    }
}
