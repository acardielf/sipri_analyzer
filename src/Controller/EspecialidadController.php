<?php

namespace App\Controller;

use App\Entity\Especialidad;
use App\Entity\Plaza;
use App\Repository\CentroRepository;
use App\Repository\CursoRepository;
use App\Repository\EspecialidadRepository;
use App\Repository\LocalidadRepository;
use App\Repository\PlazaRepository;
use App\Repository\ProvinciaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

class EspecialidadController extends AbstractController
{

    public function __construct(
        private readonly EspecialidadRepository $especialidadRepository,
        private readonly CursoRepository        $cursoRepository,
        private readonly PlazaRepository        $plazaRepository,
        private readonly ProvinciaRepository    $provinciaRepository,
    )
    {
    }

    #[Route('/{curso}/especialidades', name: 'app_especialidad_index')]
    public function index(int $curso): Response
    {
        $curso = $this->cursoRepository->findOneBy(['id' => $curso]);

        if (!$curso) {
            throw $this->createNotFoundException('Curso not found');
        }

        $especialidades = $this->especialidadRepository->getEspecialidadesByCurso($curso);

        return $this->render('especialidades.html.twig', [
            'curso' => $curso,
            'especialidades' => $especialidades,
        ]);
    }


    #[Route('{curso}/especialidad/{especialidad}/', name: 'app_especialidad_show')]
    public function especialidad(
        int    $curso,
        string $especialidad,
    ): Response
    {
        $curso = $this->cursoRepository->findOneBy(['id' => $curso]);
        $especialidad = $this->especialidadRepository->findOneBy(['id' => $especialidad]);

        if (!$curso) {
            throw $this->createNotFoundException('Curso not found');
        }
        if (!$especialidad) {
            throw $this->createNotFoundException('Especialidad not found');
        }

        $provincias = $this->provinciaRepository->findAll();
        $plazas = $this->plazaRepository->findByEspecialidadAndCurso($especialidad, $curso);

        $filtered = [];
        foreach ($provincias as $provincia) {
            $filtered[$provincia->getId()] = array_filter($plazas, function (Plaza $plaza) use ($provincia) {
                return $plaza->getCentro()->getLocalidad()->getProvincia()->getId() === $provincia->getId();
            });
        }

        return $this->render('especialidad.html.twig', [
            'curso' => $curso,
            'especialidad' => $especialidad,
            'plazas' => $filtered,
            'provincias' => $provincias,
        ]);
    }

}
