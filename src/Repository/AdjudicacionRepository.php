<?php

namespace App\Repository;

use App\Entity\Adjudicacion;
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
}
