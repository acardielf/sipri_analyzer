<?php

namespace App\Repository;

use App\Entity\Convocatoria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Convocatoria>
 */
class ConvocatoriaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Convocatoria::class);
    }

    public function remove(Convocatoria $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<Convocatoria>
     */
    public function findWithoutAdjudicacion(): array
    {
        return $this->getEntityManager()->createQuery(
            'SELECT c
                    FROM App\Entity\Convocatoria c
                    WHERE NOT EXISTS (
                        SELECT 1
                        FROM App\Entity\Adjudicacion a
                        JOIN a.plaza p
                        WHERE p.convocatoria = c
                    )'
        )->getResult();
    }
}
