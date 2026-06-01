<?php

namespace App\Controller;

use App\Repository\CentroRepository;
use App\Repository\ProvinciaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CentrosIndexController extends AbstractController
{
    public function __construct(
        private readonly ProvinciaRepository $provinciaRepository,
        private readonly CentroRepository $centroRepository,
    ) {
    }

    #[Route('/centros/', name: 'app_centros_index')]
    public function index(): Response
    {
        $provincias = $this->provinciaRepository->findBy([], ['nombre' => 'ASC']);
        $centrosPorProvincia = $this->centroRepository->countByProvincia();

        return $this->render('centros/index.html.twig', [
            'provincias' => $provincias,
            'centrosPorProvincia' => $centrosPorProvincia,
        ]);
    }
}
