<?php

namespace App\Controller;

use App\Repository\ConvocatoriaRepository;
use App\Repository\CursoRepository;
use App\Service\ChartService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;

class FormBuscarFechaController extends AbstractController
{

    public function __construct(
    ) {
    }


    #[Route('/form', name: 'app_form')]
    public function __invoke(ChartBuilderInterface $chartBuilder): Response
    {

        return $this->render('form.html.twig', [

        ]);
    }


}
