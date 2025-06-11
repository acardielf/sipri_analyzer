<?php

namespace App\Repository;

use App\Dto\PlazaDto;
use App\Entity\Plaza;
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

    public function findByAttributes(
        ?int                $convocatoriaId = null,
        ?int                $centroId = null,
        ?string             $especialidadId = null,
        ?string             $tipo = null,
        ?string             $obligatoriedad = null,
        ?\DateTimeImmutable $fechaPrevistaCese = null,
        ?int                $numero = null
    ): ?Plaza
    {
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
                ->setParameter('fechaPrevistaCese', $fechaPrevistaCese);
        }

        if ($numero !== null) {
            $qb->andWhere('p.numero = :numero')
                ->setParameter('numero', $numero);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function save(Plaza $plaza)
    {
        $this->em->persist($plaza);
        $this->em->flush();
    }

    public function findByHash(PlazaDto $dto, int $ocurrencia): ?Plaza
    {
        $hash = hash('sha256',
            $dto->convocatoria->id .
            $dto->centro->id .
            $dto->especialidad->id .
            $dto->tipoPlaza->value .
            $dto->obligatoriedadPlaza->value .
            $dto->fechaPrevistaCese?->format('Y-m-d') .
            $dto->numero .
            $ocurrencia
        );

        return $this->findOneBy(['hash' => $hash]);
    }
}
