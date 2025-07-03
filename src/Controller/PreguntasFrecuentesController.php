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

class PreguntasFrecuentesController extends AbstractController
{

    #[Route('/preguntas_frecuentes', name: 'app_faq')]
    public function __invoke(): Response
    {
        return $this->render('faq.html.twig');
    }


}
