<?php

namespace App\Repository;

use App\Entity\Adjudicacion;
use App\Entity\Curso;
use App\Entity\Especialidad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Adjudicacion>
 */
class AdjudicacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Adjudicacion::class);
    }

    public function findByConvocatoria(int $convocatoria)
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.plaza', 'p')
            ->andWhere('p.convocatoria = :convocatoria')
            ->setParameter('convocatoria', $convocatoria)
            ->orderBy('a.orden', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<Adjudicacion> $adjudicaciones
     * @return void
     */
    public function removeAll(array $adjudicaciones): void
    {
        foreach ($adjudicaciones as $adjudicacion) {
            $this->getEntityManager()->remove($adjudicacion);
        }
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }


    public function findByEspecialidadAndCurso(Especialidad $especialidad, Curso $curso)
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.plaza', 'p')
            ->join('p.convocatoria', 'c')
            ->where('p.especialidad = :especialidad')
            ->andWhere('c.curso = :curso')
            ->setParameter('especialidad', $especialidad)
            ->setParameter('curso', $curso)
            ->orderBy('c.fecha', 'ASC');

        return $qb->getQuery()->getResult();
    }

}
