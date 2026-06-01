<?php

namespace App\Controller;

use App\Repository\ConvocatoriaRepository;
use App\Repository\CursoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConvocatoriaCursoController extends AbstractController
{
    public function __construct(
        private readonly CursoRepository $cursoRepository,
        private readonly ConvocatoriaRepository $convocatoriaRepository,
    ) {
    }

    #[Route('/convocatoria/curso/{cursoId}/', name: 'app_convocatoria_curso')]
    public function index(string $cursoId): Response
    {
        $curso = $this->cursoRepository->findOneBy(['id' => $cursoId]);

        if (!$curso) {
            throw $this->createNotFoundException('Curso no encontrado');
        }

        $rows = $this->convocatoriaRepository->findByCursoInArray($cursoId);

        $convList = [];
        $totalPlazas = 0;
        foreach ($rows as $conv) {
            $plazas   = (int) $conv['plazas'];
            $vacantes = (int) $conv['vacantes'];
            $totalPlazas += $plazas;
            $convList[] = [
                'id'       => $conv['id'],
                'fecha'    => $conv['fecha'] ? $conv['fecha']->format('Y-m-d') : null,
                'plazas'   => $plazas,
                'vacantes' => $vacantes,
                'url'      => $this->generateUrl('app_convocatoria_detalle', ['id' => $conv['id']]),
            ];
        }

        $calData = [
            $cursoId => [
                'nombre'        => $curso->getNombre(),
                'convocatorias' => $convList,
            ],
        ];

        return $this->render('convocatoria/curso.html.twig', [
            'curso'       => $curso,
            'calData'     => $calData,
            'totalPlazas' => $totalPlazas,
            'total'       => count($convList),
        ]);
    }
}
