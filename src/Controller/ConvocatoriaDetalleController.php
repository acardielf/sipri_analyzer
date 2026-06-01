<?php

namespace App\Controller;

use App\Repository\ConvocatoriaRepository;
use App\Repository\PlazaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConvocatoriaDetalleController extends AbstractController
{
    public function __construct(
        private readonly ConvocatoriaRepository $convocatoriaRepository,
        private readonly PlazaRepository $plazaRepository,
    ) {
    }

    #[Route('/convocatoria/{id}/', name: 'app_convocatoria_detalle')]
    public function index(string $id): Response
    {
        $convocatoria = $this->convocatoriaRepository->find($id);

        if (!$convocatoria) {
            throw $this->createNotFoundException('Convocatoria no encontrada');
        }

        $plazas = $this->plazaRepository->findByConvocatoriaArray($id);

        $plazasByProv = [];
        $especialidades = [];
        $totalPlazas = 0;
        $vacantes = 0;
        $sustit = 0;
        $adjudicadas = 0;

        foreach ($plazas as $p) {
            $plazasByProv[$p['provNombre']][] = $p;
            $especialidades[$p['espId']] = $p['espNombre'];
            $tipoValue = $p['tipo'] instanceof \App\Enum\TipoPlazaEnum ? $p['tipo']->value : $p['tipo'];
            $tipoValue === 'VACANTE' ? $vacantes++ : $sustit++;
            if ($p['adjOrden'] !== null) $adjudicadas++;
            $totalPlazas++;
        }

        ksort($plazasByProv);
        asort($especialidades);

        return $this->render('convocatoria/detalle.html.twig', [
            'convocatoria'  => $convocatoria,
            'plazasByProv'  => $plazasByProv,
            'especialidades' => $especialidades,
            'totalPlazas'   => $totalPlazas,
            'vacantes'      => $vacantes,
            'sustit'        => $sustit,
            'adjudicadas'   => $adjudicadas,
        ]);
    }
}
