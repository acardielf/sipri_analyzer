<?php

namespace App\Controller;

use App\Repository\CentroRepository;
use App\Repository\CursoRepository;
use App\Repository\ProvinciaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CentrosProvinciaController extends AbstractController
{
    public function __construct(
        private readonly ProvinciaRepository $provinciaRepository,
        private readonly CentroRepository $centroRepository,
        private readonly CursoRepository $cursoRepository,
    ) {
    }

    #[Route('/centros/{provincia}/', name: 'app_centros_provincia')]
    public function index(string $provincia): Response
    {
        $provinciaEntity = $this->provinciaRepository->find($provincia);

        if (!$provinciaEntity) {
            throw $this->createNotFoundException('Provincia no encontrada');
        }

        $ultimoCurso = $this->cursoRepository->findLast();
        $centros = $this->centroRepository->findByProvinciaWithStats(
            $provinciaEntity->getId(),
            $ultimoCurso->getId(),
        );

        return $this->render('centros/provincia.html.twig', [
            'provincia'   => $provinciaEntity,
            'centros'     => $centros,
            'ultimoCurso' => $ultimoCurso,
        ]);
    }
}
