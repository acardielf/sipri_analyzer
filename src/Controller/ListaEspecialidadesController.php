<?php

namespace App\Controller;

use App\Repository\CuerpoRepository;
use App\Repository\CursoRepository;
use App\Repository\EspecialidadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ListaEspecialidadesController extends AbstractController
{

    public function __construct(
        private readonly EspecialidadRepository $especialidadRepository,
        private readonly CursoRepository $cursoRepository,
        private readonly CuerpoRepository $cuerpoRepository,
    ) {
    }

    #[Route('/cuerpo/{cuerpo}/', name: 'app_lista_especialidades')]
    public function __invoke(string $cuerpo): Response
    {
        $cuerpo = $this->cuerpoRepository->find($cuerpo);
        $especialidades = $this->especialidadRepository->findBy(['cuerpo' => $cuerpo]);

        return $this->render('especialidades/index.html.twig', [
            'especialidades' => $especialidades,
            'cuerpo' => $cuerpo,
            'cursos' => $this->cursoRepository->findAll(),
        ]);
    }


}
