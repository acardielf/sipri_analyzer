<?php

namespace App\Repository;

use App\Entity\Curso;
use App\Entity\Especialidad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Especialidad>
 */
class EspecialidadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Especialidad::class);
    }

    public function getEspecialidadesByCurso(Curso $curso): array
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.plazas', 'p')
            ->join('p.convocatoria', 'c')
            ->where('c.curso = :curso')
            ->setParameter('curso', $curso)
            ->distinct();
        $query = $qb->getQuery();
        $result = $query->getResult();
        return array_filter($result, function (Especialidad $especialidad) {
            return $especialidad->getNombre() !== "";
        });
    }


}
