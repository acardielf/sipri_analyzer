<?php

namespace App\Service\DtoToEntity;

use App\Dto\CentroDto;
use App\Dto\CursoDto;
use App\Entity\Centro;
use App\Entity\Curso;
use App\Repository\CentroRepository;
use App\Repository\CursoRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class CursoDtoToEntity
{

    public function __construct(
        public EntityManagerInterface $em,
        public CursoRepository        $repository,
    )
    {
    }

    public function get(CursoDto $dto, bool $persist = true): Curso
    {
        $curso = $this->repository->find($dto->id);

        if (!$curso) {

            $curso = new Curso(
                id: $dto->id,
                nombre: $dto->nombre,
                simple: $dto->simple,
            );
            if ($persist) {
                $this->em->persist($curso);
            }
        }
        return $curso;
    }

}
