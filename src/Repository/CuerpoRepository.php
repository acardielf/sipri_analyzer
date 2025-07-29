<?php

namespace App\Repository;

use App\Entity\Cuerpo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cuerpo>
 */
class CuerpoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cuerpo::class);
    }

    public function findWithEspecialidades()
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.especialidades', 'e') // supondremos que la relación es c.especialidades
            ->addSelect('e') // opcional, para evitar lazy loading
            ->getQuery()
            ->getResult();
    }
}
