<?php

namespace App\Controller;

use App\Repository\CuerpoRepository;
use App\Repository\CursoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
class ListaCuerposController extends AbstractController
{

    public function __construct(
        private readonly CursoRepository $cursoRepository,
        private readonly CuerpoRepository $cuerpoRepository,
    ) {
    }

    #[Route('/cuerpos', name: 'app_lista_cuerpos')]
    public function __invoke(): Response
    {
        $cuerpos = $this->cuerpoRepository->findWithEspecialidades();

        return $this->render('cuerpos/index.html.twig', [
            'cuerpos' => $cuerpos,
            'cursos' => $this->cursoRepository->findAll(),
        ]);
    }


}
