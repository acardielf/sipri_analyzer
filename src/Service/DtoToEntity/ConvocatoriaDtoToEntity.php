<?php

namespace App\Service\DtoToEntity;

use App\Dto\ConvocatoriaDto;
use App\Entity\Convocatoria;
use App\Repository\ConvocatoriaRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class ConvocatoriaDtoToEntity
{

    public function __construct(
        public EntityManagerInterface $em,
        public ConvocatoriaRepository $repository,
        public CursoDtoToEntity       $cursoDtoToEntity,
    )
    {
    }

    public function get(ConvocatoriaDto $dto, bool $persist = true): Convocatoria
    {
        $convocatoria = $this->repository->find($dto->id);

        if (!$convocatoria) {
            $convocatoria = new Convocatoria(
                id: $dto->id,
                nombre: $dto->nombre,
                curso: $this->cursoDtoToEntity->get($dto->curso)
            );
            if ($persist) {
                $this->em->persist($convocatoria);
            }
        }
        return $convocatoria;
    }

}
