<?php

namespace App\Controller;

use App\Entity\Plaza;
use App\Repository\CursoRepository;
use App\Repository\EspecialidadRepository;
use App\Repository\PlazaRepository;
use App\Repository\ProvinciaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlazasController extends AbstractController
{

    public function __construct(
        private readonly EspecialidadRepository $especialidadRepository,
        private readonly CursoRepository        $cursoRepository,
        private readonly PlazaRepository        $plazaRepository,
        private readonly ProvinciaRepository    $provinciaRepository,
    )
    {
    }

    #[Route('/{curso}/{especialidad}/{provincia}/plazas', name: 'app_plazas_especialidad_curso_provincia')]
    public function index(int $curso, string $especialidad, int $provincia): Response
    {
        $curso = $this->cursoRepository->findOneBy(['id' => $curso]);
        $especialidad = $this->especialidadRepository->findOneBy(['id' => $especialidad]);
        $provincia = $this->provinciaRepository->findOneBy(['id' => $provincia]);

        if (!$curso) {
            throw $this->createNotFoundException('Curso not found');
        }

        if (!$especialidad) {
            throw $this->createNotFoundException('Especialidad not found');
        }

        if (!$provincia) {
            throw $this->createNotFoundException('Provincia not found');
        }

        $plazas = $this->plazaRepository->getEspecialidadesByCursoAndProvincia($curso, $especialidad, $provincia);

        return $this->render('plazas.html.twig', [
            'curso' => $curso,
            'especialidad' => $especialidad,
            'provincia' => $provincia,
            'plazas' => $plazas,
        ]);
    }

}
