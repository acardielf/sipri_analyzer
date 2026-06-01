<?php

namespace App\Controller;

use App\Repository\CentroRepository;
use App\Repository\PlazaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CentroDetalleController extends AbstractController
{
    public function __construct(
        private readonly CentroRepository $centroRepository,
        private readonly PlazaRepository $plazaRepository,
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

        $plazasByCurso = [];
        foreach ($plazas as $p) {
            $plazasByCurso[$p['cursoNombre']][] = $p;
        }
        krsort($plazasByCurso);

        $totalPlazas = count($plazas);

        $especialidades = [];
        foreach ($plazas as $p) {
            $especialidades[$p['espId']] = true;
        }
        $totalEspecialidades = count($especialidades);

        $ultimoCurso = null;
        if (!empty($plazasByCurso)) {
            $ultimoCurso = array_key_first($plazasByCurso);
        }

        return $this->render('centro/detalle.html.twig', [
            'centro' => $centro,
            'plazasByCurso' => $plazasByCurso,
            'totalPlazas' => $totalPlazas,
            'totalEspecialidades' => $totalEspecialidades,
            'ultimoCurso' => $ultimoCurso,
        ]);
    }
}
