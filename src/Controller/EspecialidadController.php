<?php

namespace App\Controller;

use App\Entity\Especialidad;
use App\Repository\CentroRepository;
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
    )
    {
    }

    #[Route('/especialidades', name: 'app_especialidad_index')]
    public function index(): Response
    {
        $array = array_filter($this->especialidadRepository->findAll(),
            function (Especialidad $especialidad) {
                return $especialidad->getNombre() !== "";
            }
        );
        return $this->render('especialidades.html.twig', [
            'especialidades' => $array,
        ]);
    }



    #[Route('/especialidad/{id}/', name: 'app_especialidad_show')]
    public function especialidad(
        string $id,
    ): Response
    {
        return $this->render('especialidad.html.twig', [
            'especialidad' => $this->especialidadRepository->find($id),
        ]);
    }

}
