<?php

namespace App\Controller;

use App\Entity\Especialidad;
use App\Repository\CentroRepository;
use App\Repository\CursoRepository;
use App\Repository\EspecialidadRepository;
use App\Repository\LocalidadRepository;
use App\Repository\PlazaRepository;
use App\Repository\ProvinciaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }


}
