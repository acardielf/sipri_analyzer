<?php

namespace App\Repository;

use App\Entity\Convocatoria;
use App\Enum\TipoPlazaEnum;
use App\Enum\TipoProcesoEnum;
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

    public function findWithBasicDataInArray(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.id AS id')
            ->addSelect('cu.id AS curso')
            ->addSelect('cu.nombre AS cursoNombre')
            ->addSelect('c.fecha AS fecha')
            ->addSelect('c.id AS convocatoria')
            ->addSelect('COUNT(p.id) AS plazas')
            ->addSelect('SUM(CASE WHEN p.tipo = :tipoPlaza THEN 1 ELSE 0 END) AS vacantes')
            ->join('c.curso', 'cu')
            ->leftJoin('c.plazas', 'p')
            ->groupBy('c.id')
            ->addGroupBy('cu.id')
            ->setParameter('tipoPlaza', TipoPlazaEnum::VACANTE);

        $query = $qb->getQuery();
        return $query->getArrayResult();
    }
}
