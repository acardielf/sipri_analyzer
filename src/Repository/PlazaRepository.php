<?php

namespace App\Repository;

use App\Dto\PlazaDto;
use App\Entity\Curso;
use App\Entity\Especialidad;
use App\Entity\Plaza;
use App\Entity\Provincia;
use App\Enum\ObligatoriedadPlazaEnum;
use App\Enum\TipoPlazaEnum;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plaza>
 */
class PlazaRepository extends ServiceEntityRepository
{
    protected EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry)
    {
        $this->em = $registry->getManager();
        parent::__construct($registry, Plaza::class);
    }

    /**
     * @param int|null $convocatoriaId
     * @param string|null $centroId
     * @param string|null $especialidadId
     * @param TipoPlazaEnum|null $tipo
     * @param ObligatoriedadPlazaEnum|null $obligatoriedad
     * @param DateTimeImmutable|null $fechaPrevistaCese
     * @param int|null $numero
     * @return array<Plaza>|null
     */
    public function findByAttributes(
        ?int $convocatoriaId = null,
        ?string $centroId = null,
        ?string $especialidadId = null,
        ?TipoPlazaEnum $tipo = null,
        ?ObligatoriedadPlazaEnum $obligatoriedad = null,
        ?DateTimeImmutable $fechaPrevistaCese = null,
        ?int $numero = null
    ): ?array {
        $qb = $this->createQueryBuilder('p');

        if ($convocatoriaId) {
            $qb->andWhere('p.convocatoria = :convocatoriaId')
                ->setParameter('convocatoriaId', $convocatoriaId);
        }

        if ($centroId) {
            $qb->andWhere('p.centro = :centroId')
                ->setParameter('centroId', $centroId);
        }

        if ($especialidadId) {
            $qb->andWhere('p.especialidad = :especialidadId')
                ->setParameter('especialidadId', $especialidadId);
        }

        if ($tipo) {
            $qb->andWhere('p.tipo = :tipo')
                ->setParameter('tipo', $tipo);
        }

        if ($obligatoriedad) {
            $qb->andWhere('p.obligatoriedad = :obligatoriedad')
                ->setParameter('obligatoriedad', $obligatoriedad);
        }

        if ($fechaPrevistaCese) {
            $qb->andWhere('p.fechaPrevistaCese = :fechaPrevistaCese')
                ->setParameter('fechaPrevistaCese', $fechaPrevistaCese->format('Y-m-d'));
        }

        if ($numero !== null) {
            $qb->andWhere('p.numero = :numero')
                ->setParameter('numero', $numero);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<Plaza>|Plaza $plaza
     * @param bool $clear
     * @return void
     */
    public function save(array|Plaza $plaza, bool $clear = false): void
    {
        if (is_array($plaza)) {
            foreach ($plaza as $p) {
                if (!$p instanceof Plaza) {
                    throw new \InvalidArgumentException('Expected Plaza entity in array.');
                }
                $this->em->persist($p);
            }
        } elseif ($plaza instanceof Plaza) {
            $this->em->persist($plaza);
        } else {
            throw new \InvalidArgumentException('Expected Plaza or array of Plaza entities.');
        }

        $this->em->flush();
        if ($clear) {
            $this->em->clear();
        }
    }

    public function findByHash(PlazaDto $dto, int $ocurrencia): ?Plaza
    {
        return $this->findOneBy(['hash' => $dto->getHash($ocurrencia)]);
    }

    public function findByEspecialidadAndCurso(Especialidad $especialidad, Curso $curso)
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.convocatoria', 'c')
            ->join('p.centro', 'cc')
            ->join('cc.localidad', 'l')
            ->join('l.provincia', 'prov')
            ->where('p.especialidad = :especialidad')
            ->andWhere('c.curso = :curso')
            ->orderBy('prov.nombre', 'ASC')
            ->addOrderBy('l.nombre', 'ASC')
            ->setParameter('especialidad', $especialidad)
            ->setParameter('curso', $curso);

        return $qb->getQuery()->getResult();
    }


    /**
     * @param Curso $curso
     * @param Especialidad $especialidad
     * @param Provincia $provincia
     * @return array<Plaza>
     */
    public function getEspecialidadesByCursoAndProvincia(
        Curso $curso,
        Especialidad $especialidad,
        Provincia $provincia
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->join('p.convocatoria', 'c')
            ->join('p.centro', 'cc')
            ->join('cc.localidad', 'l')
            ->join('l.provincia', 'prov')
            ->leftJoin('p.adjudicaciones', 'a')
            ->where('p.especialidad = :especialidad')
            ->andWhere('c.curso = :curso')
            ->andWhere('prov.id = :provincia')
            ->orderBy('p.convocatoria', 'DESC')
            ->addOrderBy('a.orden', 'DESC')
            ->addOrderBy('p.centro', 'ASC')
            ->setParameter('especialidad', $especialidad)
            ->setParameter('curso', $curso)
            ->setParameter('provincia', $provincia);

        return $qb->getQuery()->getResult();
    }

    public function remove(Plaza $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param array<Plaza> $plazas
     * @return void
     */
    public function removeAll(iterable $plazas): void
    {
        foreach ($plazas as $plaza) {
            $this->getEntityManager()->remove($plaza);
        }
        $this->getEntityManager()->flush();
    }


    /**
     * @param array<Plaza>|null $plazas
     * @return array<Plaza>
     */
    public function findPlazasDesiertas(array $plazas): array
    {
        $dql = '
        SELECT p
        FROM App\Entity\Plaza p
        LEFT JOIN p.adjudicaciones a
        WHERE a.id IS NULL
          AND EXISTS (
              SELECT 1
              FROM App\Entity\Adjudicacion a2
              JOIN a2.plaza p2
              WHERE p2.convocatoria = p.convocatoria
          )
        ';

        $dql .= ' AND p IN (:plazas)';

        $query = $this->getEntityManager()->createQuery($dql);

        $query->setParameter('plazas', $plazas);

        return $query->getResult();
    }


}
