<?php

namespace App\Controller;

use App\Entity\Plaza;
use App\Repository\CentroRepository;
use App\Repository\CursoRepository;
use App\Repository\EspecialidadRepository;
use App\Repository\LocalidadRepository;
use App\Repository\PlazaRepository;
use App\Repository\ProvinciaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EspecialidadIndexController extends AbstractController
{

    public function __construct(
        private readonly CursoRepository $cursoRepository,
        private readonly ProvinciaRepository $provinciaRepository,
        private readonly PlazaRepository $plazaRepository,
        private readonly EspecialidadRepository $especialidadRepository,
    ) {
    }

    #[Route('/especialidad/{especialidad}/', name: 'app_especialidad_index')]
    public function index(string $especialidad): Response
    {
        $especialidad = $this->especialidadRepository->findOneBy(['id' => $especialidad]);

        if (!$especialidad) {
            throw $this->createNotFoundException('Especialidad not found');
        }

        $result = [];
        foreach ($this->cursoRepository->findAll() as $curso) {
            foreach ($this->provinciaRepository->findAll() as $provincia) {
                $result[$curso->getId()][$provincia->getId(
                )] = $this->plazaRepository->getEspecialidadesByCursoAndProvincia($curso, $especialidad, $provincia);
            }
        }


        return $this->render('especialidades/curso.html.twig', [
            'provincias' => $this->provinciaRepository->findAll(),
            'cursos' => $this->cursoRepository->findAll(),
            'especialidad' => $especialidad,
            'plazasFiltradas' => $result,
        ]);
    }

}
