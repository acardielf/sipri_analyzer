<?php

namespace App\Controller;

use App\Repository\ConvocatoriaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    public function __construct(
        protected readonly ConvocatoriaRepository $convocatoriaRepository,
    ) {
    }

    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->render('index.html.twig', [
            'convocatoria' => $this->convocatoriaRepository->findOneBy([], ['fecha' => 'DESC']),
        ]);
    }
    
}
