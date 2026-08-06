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

    public function __construct(
        ManagerRegistry $registry,
        private readonly ConvocatoriaRepository $convocatoriaRepository,
    ) {
        $this->em = $registry->getManager();
        parent::__construct($registry, Plaza::class);
    }

    /**
     * @param int|null $convocatoriaId
     * @param string|null $centroId
     * @param string|null $especialidadId
     * @param TipoPlazaEnum|null $tipo
     * @param ObligatoriedadPlazaEnum|null $obligatoriedad
     * @param string|null $fechaPrevistaCese
     * @param int|null $numero
     * @return array<Plaza>|null
     */
    public function findByAttributes(
        ?int $convocatoriaId = null,
        ?string $centroId = null,
        ?string $especialidadId = null,
        ?TipoPlazaEnum $tipo = null,
        ?ObligatoriedadPlazaEnum $obligatoriedad = null,
        ?string $fechaPrevistaCese = null,
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

        if (!is_null($fechaPrevistaCese)) {
            /*
             * Parece que las primeras convocatorias, las vacantes que salen
             * con fecha prevista de cese el último día de curso, luego en la adjudicación
             * aparecen como sin fecha prevista de cese, al tratarse de una vacante de curso entero.
             *
             * Por eso hay que hacer una excepción para las vacantes:
             */

            if ($fechaPrevistaCese === '' && $tipo !== TipoPlazaEnum::VACANTE) {
                $qb->andWhere('p.fechaPrevistaCese IS NULL');
            }

            if ($fechaPrevistaCese !== '') {
                $fechaPrevistaCeseParseada = DateTimeImmutable::createFromFormat('!d/m/y', $fechaPrevistaCese);
                $qb->andWhere('p.fechaPrevistaCese = :fechaPrevistaCese')
                    ->setParameter('fechaPrevistaCese', $fechaPrevistaCeseParseada->format('Y-m-d'));
            }
        }

        if ($numero !== null) {
            $qb->andWhere('p.numero = :numero')
                ->setParameter('numero', $numero);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Plaza $plaza
     * @param bool $clear
     * @return void
     */
    public function save(Plaza $plaza, bool $clear = false): void
    {
        $this->em->persist($plaza);
        $this->em->flush();
        if ($clear) {
            $this->em->clear();
        }
    }

    public function findByHash(PlazaDto $dto, int $ocurrencia): ?Plaza
    {
        return $this->findOneBy(['hash' => $dto->getHash($ocurrencia)]);
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

    /**
     * Todas las plazas de una especialidad en un curso, de todas las provincias.
     *
     * @param Curso $curso
     * @param Especialidad $especialidad
     * @return array<Plaza>
     */
    public function getEspecialidadesByCurso(Curso $curso, Especialidad $especialidad): array
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('c', 'cc', 'l', 'prov', 'a')
            ->join('p.convocatoria', 'c')
            ->join('p.centro', 'cc')
            ->join('cc.localidad', 'l')
            ->join('l.provincia', 'prov')
            ->leftJoin('p.adjudicaciones', 'a')
            ->where('p.especialidad = :especialidad')
            ->andWhere('c.curso = :curso')
            ->orderBy('c.fecha', 'DESC')
            ->addOrderBy('prov.nombre', 'ASC')
            ->addOrderBy('a.orden', 'ASC')
            ->setParameter('especialidad', $especialidad)
            ->setParameter('curso', $curso);

        return $qb->getQuery()->getResult();
    }

    /**
     * Puestos sin cubrir por plaza: `[plazaId => puestos desiertos]`.
     *
     * Una fila de `plaza` puede ofertar varios puestos (`numero`), así que puede
     * quedar desierta del todo o solo en parte. Solo se consideran las
     * convocatorias de las que se conocen adjudicaciones; en las demás la
     * ausencia de adjudicación significa "sin datos", no "desierta".
     *
     * A diferencia de findPlazasDesiertas(), no necesita recibir el listado de
     * plazas, que en especialidades grandes desborda el límite de parámetros de
     * SQLite.
     *
     * @return array<int, int>
     */
    public function findSinCubrirPorPlaza(
        Curso $curso,
        Especialidad $especialidad,
        ?Provincia $provincia = null
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id AS id')
            ->addSelect('p.numero - COUNT(a.id) AS sinCubrir')
            ->join('p.convocatoria', 'c')
            ->join('p.centro', 'cc')
            ->join('cc.localidad', 'l')
            ->leftJoin('p.adjudicaciones', 'a')
            ->where('p.especialidad = :especialidad')
            ->andWhere('c.curso = :curso')
            ->andWhere('c.id IN (:convocatoriasConAdjudicaciones)')
            ->groupBy('p.id')
            ->addGroupBy('p.numero')
            ->having('p.numero - COUNT(a.id) > 0')
            ->setParameter('especialidad', $especialidad)
            ->setParameter('curso', $curso)
            ->setParameter(
                'convocatoriasConAdjudicaciones',
                $this->convocatoriaRepository->findIdsConAdjudicaciones()
            );

        if ($provincia) {
            $qb->andWhere('l.provincia = :provincia')
                ->setParameter('provincia', $provincia);
        }

        $sinCubrir = [];
        foreach ($qb->getQuery()->getArrayResult() as $fila) {
            $sinCubrir[(int)$fila['id']] = (int)$fila['sinCubrir'];
        }

        return $sinCubrir;
    }

    /**
     * Agregados por curso y provincia de una especialidad.
     *
     * `totalPlazas` cuenta adjudicaciones (una plaza puede tener varias y las
     * desiertas quedan fuera), mientras que el resto de métricas cuentan plazas
     * ofertadas. Son dos universos distintos y por eso se calculan por separado.
     *
     * @param Especialidad $especialidad
     * @return array<array{cursoId: string, provId: string, totalPlazas: int, plazasOfertadas: int,
     *                     vacantes: int, vacantesInicio: int, sustituciones: int, desiertas: int,
     *                     minOrden: int, maxOrden: int}>
     */
    public function getEspecialidadAsArray(Especialidad $especialidad): array
    {
        $result = [];

        foreach ($this->getPlazasOfertadasAsArray($especialidad) as $fila) {
            $clave = $fila['cursoId'] . '-' . $fila['provId'];

            $result[$clave] = [
                'cursoId' => $fila['cursoId'],
                'provId' => $fila['provId'],
                'totalPlazas' => 0,
                'plazasOfertadas' => (int)$fila['plazasOfertadas'],
                'vacantes' => (int)$fila['vacantes'],
                'vacantesInicio' => (int)$fila['vacantesInicio'],
                'sustituciones' => (int)$fila['sustituciones'],
                'desiertas' => (int)$fila['plazasEnConvocatoriasConAdjudicaciones'],
                'minOrden' => 0,
                'maxOrden' => 0,
            ];
        }

        foreach ($this->getAdjudicacionesAsArray($especialidad) as $fila) {
            $clave = $fila['cursoId'] . '-' . $fila['provId'];

            $result[$clave]['totalPlazas'] = (int)$fila['totalPlazas'];
            $result[$clave]['minOrden'] = (int)$fila['minOrden'];
            $result[$clave]['maxOrden'] = (int)$fila['maxOrden'];
            // Una plaza puede ofertar varios puestos: lo no adjudicado queda desierto
            $result[$clave]['desiertas'] -= (int)$fila['totalPlazas'];
        }

        return array_values($result);
    }

    /**
     * Conteos sobre plazas ofertadas: sin join con adjudicaciones, para que una
     * plaza adjudicada varias veces no cuente más de una vez.
     */
    private function getPlazasOfertadasAsArray(Especialidad $especialidad): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('curso.id as cursoId')
            ->addSelect('prov.id as provId')
            ->addSelect('SUM(p.numero) AS plazasOfertadas')
            ->addSelect('SUM(CASE WHEN p.tipo = :vacante THEN p.numero ELSE 0 END) AS vacantes')
            ->addSelect(
                'SUM(CASE WHEN p.tipo = :vacante AND c.id IN (:convocatoriasInicio) THEN p.numero ELSE 0 END) AS vacantesInicio'
            )
            ->addSelect('SUM(CASE WHEN p.tipo = :sustitucion THEN p.numero ELSE 0 END) AS sustituciones')
            ->addSelect(
                'SUM(CASE WHEN c.id IN (:convocatoriasConAdjudicaciones) THEN p.numero ELSE 0 END) AS plazasEnConvocatoriasConAdjudicaciones'
            )
            ->join('p.convocatoria', 'c')
            ->join('c.curso', 'curso')
            ->join('p.centro', 'cc')
            ->join('cc.localidad', 'l')
            ->join('l.provincia', 'prov')
            ->where('p.especialidad = :especialidad')
            ->groupBy('c.curso')
            ->addGroupBy('l.provincia')
            ->orderBy('c.curso', 'ASC')
            ->addOrderBy('l.provincia', 'ASC')
            ->setParameter('especialidad', $especialidad)
            ->setParameter('vacante', TipoPlazaEnum::VACANTE)
            ->setParameter('sustitucion', TipoPlazaEnum::SUSTITUCION)
            ->setParameter('convocatoriasInicio', $this->convocatoriaRepository->findIdsInicioCurso())
            ->setParameter(
                'convocatoriasConAdjudicaciones',
                $this->convocatoriaRepository->findIdsConAdjudicaciones()
            );

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Recuento de adjudicaciones y rango de posiciones llamadas.
     */
    private function getAdjudicacionesAsArray(Especialidad $especialidad): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('curso.id as cursoId')
            ->addSelect('prov.id as provId')
            ->addSelect('COUNT(p.id) AS totalPlazas')
            ->addSelect('MIN(CASE WHEN a.orden > 0 THEN a.orden ELSE 0 END) AS minOrden')
            ->addSelect('MAX(CASE WHEN a.orden > 0 THEN a.orden ELSE 0 END) AS maxOrden')
            ->join('p.convocatoria', 'c')
            ->join('c.curso', 'curso')
            ->join('p.centro', 'cc')
            ->join('cc.localidad', 'l')
            ->join('l.provincia', 'prov')
            ->join('p.adjudicaciones', 'a')
            ->where('p.especialidad = :especialidad')
            ->groupBy('c.curso')
            ->addGroupBy('l.provincia')
            ->orderBy('c.curso', 'ASC')
            ->addOrderBy('l.provincia', 'ASC')
            ->setParameter('especialidad', $especialidad);

        return $qb->getQuery()->getArrayResult();
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

    public function findVacantesByCursoEspecialidadAndProvincia(
        Curso $curso,
        Especialidad $especialidad,
        Provincia $provincia
    ): iterable {
        $qb = $this->createQueryBuilder('p')
            ->join('p.convocatoria', 'c')
            ->join('p.centro', 'cc')
            ->join('cc.localidad', 'l')
            ->join('l.provincia', 'prov')
            ->where('c.curso = :curso')
            ->andWhere('p.especialidad = :especialidad')
            ->andWhere('prov.id = :provincia')
            ->andWhere('p.tipo = :tipo')
            ->setParameter('curso', $curso)
            ->setParameter('especialidad', $especialidad)
            ->setParameter('provincia', $provincia)
            ->setParameter('tipo', TipoPlazaEnum::VACANTE);

        return $qb->getQuery()->getResult();
    }

    public function findByConvocatoriaArray(string $convocatoriaId): array
    {
        $dql = '
            SELECT
                p.id,
                p.tipo,
                p.obligatoriedad,
                p.numero,
                p.fechaPrevistaCese,
                esp.id AS espId,
                esp.nombre AS espNombre,
                prov.id AS provId,
                prov.nombre AS provNombre,
                l.nombre AS localidad,
                centro.id AS centroId,
                centro.nombre AS centroNombre,
                MIN(a.orden) AS adjOrden
            FROM App\Entity\Plaza p
            JOIN p.especialidad esp
            JOIN p.centro centro
            JOIN centro.localidad l
            JOIN l.provincia prov
            LEFT JOIN p.adjudicaciones a
            WHERE p.convocatoria = :convocatoriaId
            GROUP BY p.id, p.tipo, p.obligatoriedad, p.numero, p.fechaPrevistaCese,
                     esp.id, esp.nombre, prov.id, prov.nombre, l.nombre,
                     centro.id, centro.nombre
            ORDER BY prov.nombre ASC, esp.nombre ASC
        ';

        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('convocatoriaId', $convocatoriaId)
            ->getArrayResult();
    }

    public function findByCentroArray(string $centroId): array
    {
        $dql = '
            SELECT
                p.id,
                p.tipo,
                p.obligatoriedad,
                p.numero,
                p.fechaPrevistaCese,
                esp.id AS espId,
                esp.nombre AS espNombre,
                cu.id AS cursoId,
                cu.nombre AS cursoNombre,
                conv.id AS convId,
                conv.fecha AS convFecha,
                MIN(a.orden) AS adjOrden
            FROM App\Entity\Plaza p
            JOIN p.especialidad esp
            JOIN p.convocatoria conv
            JOIN conv.curso cu
            LEFT JOIN p.adjudicaciones a
            WHERE p.centro = :centroId
            GROUP BY p.id, p.tipo, p.obligatoriedad, p.numero, p.fechaPrevistaCese,
                     esp.id, esp.nombre, cu.id, cu.nombre, conv.id, conv.fecha
            ORDER BY cu.id DESC, conv.id DESC, esp.nombre ASC
        ';

        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('centroId', $centroId)
            ->getArrayResult();
    }

}
