<?php

namespace App\Controller;

use App\Repository\ConvocatoriaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConvocatoriaIndexController extends AbstractController
{
    public function __construct(
        private readonly ConvocatoriaRepository $convocatoriaRepository,
    ) {
    }

    #[Route('/convocatoria/', name: 'app_convocatoria_index')]
    public function index(): Response
    {
        $rows = $this->convocatoriaRepository->findWithBasicDataInArray();

        $cursos = [];
        foreach ($rows as $conv) {
            $cid = $conv['curso'];
            if (!isset($cursos[$cid])) {
                $cursos[$cid] = [
                    'id'      => $cid,
                    'nombre'  => $conv['cursoNombre'],
                    'total'   => 0,
                    'plazas'  => 0,
                    'primera' => null,
                    'ultima'  => null,
                ];
            }
            $cursos[$cid]['total']++;
            $cursos[$cid]['plazas'] += (int) $conv['plazas'];
            if ($conv['fecha'] && (!$cursos[$cid]['primera'] || $conv['fecha'] < $cursos[$cid]['primera'])) {
                $cursos[$cid]['primera'] = $conv['fecha'];
            }
            if ($conv['fecha'] && (!$cursos[$cid]['ultima'] || $conv['fecha'] > $cursos[$cid]['ultima'])) {
                $cursos[$cid]['ultima'] = $conv['fecha'];
            }
        }

        krsort($cursos);

        return $this->render('convocatoria/index.html.twig', [
            'cursos' => $cursos,
        ]);
    }
}
