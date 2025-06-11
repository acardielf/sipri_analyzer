<?php

namespace App\Repository;

use App\Entity\Localidad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Localidad>
 */
class LocalidadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Localidad::class);
    }

    public function findByNombreAndProvincia(string $nombre, int $provinceId): ?Localidad
    {
        $qb = $this->createQueryBuilder('l')
            ->andWhere('l.nombre = :nombre')
            ->setParameter('nombre', $nombre);

        $qb->andWhere('l.provincia = :provinceId')
            ->setParameter('provinceId', $provinceId);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
