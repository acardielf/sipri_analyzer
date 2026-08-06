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
    /** @var array<string>|null */
    private ?array $idsInicioCurso = null;

    /** @var array<string>|null */
    private ?array $idsConAdjudicaciones = null;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Convocatoria::class);
    }

    /**
     * Convocatorias publicadas en septiembre: son las que ofertan las vacantes
     * "sobrevenidas" o iniciales de cada curso.
     *
     * @return array<string>
     */
    public function findIdsInicioCurso(): array
    {
        if ($this->idsInicioCurso !== null) {
            return $this->idsInicioCurso;
        }

        $convocatorias = $this->createQueryBuilder('c')
            ->select('c.id AS id, c.fecha AS fecha')
            ->where('c.fecha IS NOT NULL')
            ->getQuery()
            ->getArrayResult();

        $ids = [];
        foreach ($convocatorias as $convocatoria) {
            if ($convocatoria['fecha']->format('m') === '09') {
                $ids[] = $convocatoria['id'];
            }
        }

        return $this->idsInicioCurso = $ids;
    }

    /**
     * Convocatorias con al menos una adjudicación conocida. Permite distinguir
     * una plaza desierta de una plaza cuyas adjudicaciones no se han extraído.
     *
     * @return array<string>
     */
    public function findIdsConAdjudicaciones(): array
    {
        if ($this->idsConAdjudicaciones !== null) {
            return $this->idsConAdjudicaciones;
        }

        $convocatorias = $this->getEntityManager()->createQuery(
            'SELECT c.id AS id
                    FROM App\Entity\Convocatoria c
                    WHERE EXISTS (
                        SELECT 1
                        FROM App\Entity\Adjudicacion a
                        JOIN a.plaza p
                        WHERE p.convocatoria = c
                    )'
        )->getArrayResult();

        return $this->idsConAdjudicaciones = array_column($convocatorias, 'id');
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

    public function findByCursoInArray(string $cursoId): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id AS id, c.fecha AS fecha')
            ->addSelect('COUNT(p.id) AS plazas')
            ->addSelect('SUM(CASE WHEN p.tipo = :tipoPlaza THEN 1 ELSE 0 END) AS vacantes')
            ->join('c.curso', 'cu')
            ->leftJoin('c.plazas', 'p')
            ->where('cu.id = :cursoId')
            ->groupBy('c.id')
            ->orderBy('c.fecha', 'ASC')
            ->setParameter('cursoId', $cursoId)
            ->setParameter('tipoPlaza', TipoPlazaEnum::VACANTE)
            ->getQuery()
            ->getArrayResult();
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
