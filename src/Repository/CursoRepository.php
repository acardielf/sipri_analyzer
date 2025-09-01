<?php

namespace App\Repository;

use App\Entity\Curso;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Curso>
 */
class CursoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Curso::class);
    }

    public function findLast(): Curso
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1);

        $query = $qb->getQuery();
        $result = $query->getResult();

        if (empty($result)) {
            throw new \RuntimeException('No se ha encontrado ningún curso.');
        }

        return $result[0];
    }

    public function findPrevious(Curso $curso): ?Curso
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.id < :currentId')
            ->setParameter('currentId', $curso->getId())
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1);

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result[0] ?? null;
    }

    /**
     * @return iterable<Curso>
     */
    public function findAllDescent(): iterable
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC');

        $query = $qb->getQuery();
        return $query->getResult();
    }

}
