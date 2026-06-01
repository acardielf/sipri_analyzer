<?php

namespace App\Controller;

use App\Entity\Adjudicacion;
use App\Entity\Curso;
use App\Entity\Especialidad;
use App\Repository\AdjudicacionRepository;
use App\Repository\CursoRepository;
use App\Repository\EspecialidadRepository;
use App\Repository\ProvinciaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class BuscadorDataController extends AbstractController
{
    public function __construct(
        private readonly EspecialidadRepository $especialidadRepository,
        private readonly AdjudicacionRepository $adjudicacionRepository,
        private readonly CursoRepository $cursoRepository,
        private readonly ProvinciaRepository $provinciaRepository,
    ) {}

    #[Route('/buscador/data/{especialidadId}.json', name: 'app_buscador_data')]
    public function __invoke(string $especialidadId): JsonResponse
    {
        $especialidad = $this->especialidadRepository->findOneBy(['id' => $especialidadId]);

        if (!$especialidad) {
            throw $this->createNotFoundException();
        }

        $provincias = $this->provinciaRepository->findAll();
        $provinciasMap = [];
        foreach ($provincias as $p) {
            $provinciasMap[$p->getId()] = $p->getNombre();
        }

        $cursoLast = $this->cursoRepository->findLast();
        $previo1 = $this->cursoRepository->findPrevious($cursoLast);
        $previo2 = $previo1 ? $this->cursoRepository->findPrevious($previo1) : null;
        $previo3 = $previo2 ? $this->cursoRepository->findPrevious($previo2) : null;

        $cursos = array_filter([$cursoLast, $previo1, $previo2, $previo3]);
        $cursosNombres = array_values(array_map(fn(Curso $c) => $c->getNombre(), $cursos));

        $posiciones = [];
        foreach ($cursos as $curso) {
            foreach ($this->buildAdjudicaciones($especialidad, $curso) as $orden => $provinciaData) {
                foreach ($provinciaData as $provId => $entries) {
                    foreach ($entries as $entry) {
                        $posiciones[$orden][$curso->getNombre()][] = [
                            'f' => $entry['fechaMin'],
                            't' => $entry['tipo'],
                            'p' => (string) $provId,
                        ];
                    }
                }
            }
        }

        ksort($posiciones);

        return $this->json([
            'cursos' => $cursosNombres,
            'provincias' => $provinciasMap,
            'posiciones' => $posiciones,
        ]);
    }

    private function buildAdjudicaciones(Especialidad $especialidad, Curso $curso): array
    {
        $raw = $this->adjudicacionRepository->findByEspecialidadAndCurso($especialidad, $curso);
        $result = [];
        $i = 0;

        /** @var Adjudicacion $adj */
        foreach ($raw as $adj) {
            $prov = $adj->getPlaza()->getCentro()->getLocalidad()->getProvincia()->getId();
            $fecha = $adj->getPlaza()->getConvocatoria()->getFecha();
            $orden = $adj->getOrden();

            $result[$orden][$prov][$i] = [
                'fechaMin' => $fecha->format('d/m/y'),
                'tipo'     => $adj->getPlaza()->getTipo()->getShortLabel(),
            ];
            $i++;
        }

        ksort($result);
        return $result;
    }
}
