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

    public function findAllForSelect(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.id, e.nombre, c.id as cuerpoId, c.nombre as cuerpoNombre')
            ->join('e.cuerpo', 'c')
            ->where('e.nombre != :empty')
            ->setParameter('empty', '')
            ->orderBy('c.id', 'ASC')
            ->addOrderBy('e.nombre', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param array<Curso> $cursos
     * @return array<string> IDs de especialidades con plazas en alguno de los cursos dados
     */
    public function findActivasEnCursos(array $cursos): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.id')
            ->join('e.plazas', 'p')
            ->join('p.convocatoria', 'c')
            ->where('c.curso IN (:cursos)')
            ->setParameter('cursos', $cursos)
            ->distinct()
            ->getQuery()
            ->getSingleColumnResult();
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
