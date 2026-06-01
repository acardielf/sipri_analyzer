<?php

namespace App\Repository;

use App\Entity\Centro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Centro>
 */
class CentroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Centro::class);
    }

    public function findByProvinciaWithStats(string $provinciaId, string $lastCursoId): array
    {
        $dql = '
            SELECT
                c.id,
                c.nombre,
                l.nombre AS localidad,
                COUNT(p.id) AS totalPlazas,
                SUM(CASE WHEN cu.id = :lastCursoId THEN 1 ELSE 0 END) AS plazasUltimoCurso,
                MAX(conv.fecha) AS ultimaFecha
            FROM App\Entity\Centro c
            JOIN c.localidad l
            JOIN l.provincia prov
            LEFT JOIN c.plazas p
            LEFT JOIN p.convocatoria conv
            LEFT JOIN conv.curso cu
            WHERE prov.id = :provinciaId
              AND c.id NOT IN (:ocep)
            GROUP BY c.id, c.nombre, l.nombre
            ORDER BY l.nombre ASC, c.nombre ASC
        ';

        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('provinciaId', $provinciaId)
            ->setParameter('lastCursoId', $lastCursoId)
            ->setParameter('ocep', Centro::OCEP_OTROS_CENTROS)
            ->getArrayResult();
    }

    public function countByProvincia(): array
    {
        $dql = '
            SELECT
                prov.id AS provId,
                prov.nombre AS provNombre,
                COUNT(c.id) AS total
            FROM App\Entity\Centro c
            JOIN c.localidad l
            JOIN l.provincia prov
            WHERE c.id NOT IN (:ocep)
            GROUP BY prov.id, prov.nombre
        ';

        $rows = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('ocep', Centro::OCEP_OTROS_CENTROS)
            ->getArrayResult();

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['provId']] = $row;
        }

        return $indexed;
    }

}
