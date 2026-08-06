<?php

namespace App\Repository;

use App\Entity\Curso;
use DateTimeImmutable;
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

    /**
     * Cursos ya iniciados, de más antiguo a más reciente.
     *
     * El curso siguiente se siembra por migración antes de que empiece (para
     * que su columna exista el 1 de septiembre), así que ninguna vista debe
     * verlo hasta esa fecha.
     *
     * @return array<Curso>
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.id <= :anioActual')
            ->setParameter('anioActual', (string)self::anioDelCurso(new DateTimeImmutable()))
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Último curso ya iniciado: los cursos sembrados por adelantado no cuentan
     * hasta que llega su 1 de septiembre.
     */
    public function findLast(?DateTimeImmutable $hoy = null): Curso
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.id <= :anioActual')
            ->setParameter('anioActual', (string)self::anioDelCurso($hoy ?? new DateTimeImmutable()))
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
     * Cursos ya iniciados, del más reciente al más antiguo.
     *
     * @return iterable<Curso>
     */
    public function findAllDescent(?DateTimeImmutable $hoy = null): iterable
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.id <= :anioActual')
            ->setParameter('anioActual', (string)self::anioDelCurso($hoy ?? new DateTimeImmutable()))
            ->orderBy('c.id', 'DESC');

        $query = $qb->getQuery();
        return $query->getResult();
    }

    /**
     * Cursos a mostrar en las tablas, del más reciente al más antiguo.
     *
     * Descarta los cursos que aún no han empezado y garantiza que el curso en
     * marcha tenga columna aunque todavía no se haya publicado ninguna de sus
     * convocatorias.
     *
     * @return array<Curso>
     */
    public function findAllParaTabla(?DateTimeImmutable $hoy = null): array
    {
        $hoy ??= new DateTimeImmutable();
        $anioActual = self::anioDelCurso($hoy);

        $cursos = [...$this->findAllDescent($hoy)];

        if (!isset($cursos[0]) || (int)$cursos[0]->getId() !== $anioActual) {
            array_unshift($cursos, self::cursoTransitorio($anioActual));
        }

        return $cursos;
    }

    /**
     * Un curso escolar arranca el 1 de septiembre y toma el año de ese septiembre.
     */
    public static function anioDelCurso(DateTimeImmutable $fecha): int
    {
        $anio = (int)$fecha->format('Y');

        return (int)$fecha->format('n') >= 9 ? $anio : $anio - 1;
    }

    /**
     * Curso no persistido, con los mismos valores que generaría CursoDto::fromYear().
     */
    private static function cursoTransitorio(int $anio): Curso
    {
        return new Curso(
            id: (string)$anio,
            nombre: sprintf('%d/%d', $anio, $anio + 1),
            simple: (string)$anio,
        );
    }

}
