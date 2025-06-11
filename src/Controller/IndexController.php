<?php

namespace App\Controller;

use App\Repository\CentroRepository;
use App\Repository\LocalidadRepository;
use App\Repository\PlazaRepository;
use App\Repository\ProvinciaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    public function __construct(
        private readonly PlazaRepository     $plazaRepository,
        private readonly CentroRepository    $centroRepository,
        private readonly ProvinciaRepository $provinciaRepository,
        private readonly LocalidadRepository $localidadRepository,
    )
    {
    }

    #[Route('/')]
    public function index(): Response
    {
        return $this->render('index.html.twig', [
            'provincias' => $this->provinciaRepository->findAll(),
            'localidades' => $this->localidadRepository->findAll(),
            'centros' => $this->centroRepository->findAll(),
        ]);
    }

}
