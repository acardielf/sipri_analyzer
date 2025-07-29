<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PreguntasFrecuentesController extends AbstractController
{

    #[Route('/preguntas_frecuentes', name: 'app_faq')]
    public function __invoke(): Response
    {
        return $this->render('faq.html.twig');
    }


}
