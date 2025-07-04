<?php

namespace App\Service\DtoToEntity;

use App\Dto\LocalidadDto;
use App\Entity\Localidad;
use App\Repository\LocalidadRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class LocalidadDtoToEntity
{

    public function __construct(
        public EntityManagerInterface $em,
        public LocalidadRepository    $repository,
        public ProvinciaDtoToEntity   $provinciaDtoToEntity,
    )
    {
    }

    public function get(LocalidadDto $dto, bool $persist = true): Localidad
    {
        $localidad = $this->repository->findByNombreAndProvincia($dto->nombre, $dto->provincia->id);

        if (!$localidad) {
            $localidad = new Localidad(
                nombre: $dto->nombre,
                provincia: $this->provinciaDtoToEntity->get($dto->provincia),
            );
        }

        if ($persist) {
            $this->em->persist($localidad);
        }
        return $localidad;
    }

}
