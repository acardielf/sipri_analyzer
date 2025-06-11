<?php

namespace App\Service\DtoToEntity;

use App\Dto\CentroDto;
use App\Entity\Centro;
use App\Repository\CentroRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class CentroDtoToEntity
{

    public function __construct(
        public EntityManagerInterface $em,
        public CentroRepository       $repository,
        public LocalidadDtoToEntity   $localidadDtoToEntity,
    )
    {
    }

    public function get(CentroDto $dto, bool $persist = true): Centro
    {
        $centro = $this->repository->find($dto->id);

        if (!$centro) {

            $localidad = $this->localidadDtoToEntity->get($dto->localidad);

            $centro = new Centro(
                id: $dto->id,
                nombre: $dto->nombre,
                localidad: $localidad,
            );
            if ($persist) {
                $this->em->persist($centro);
            }
        }
        return $centro;
    }

}
