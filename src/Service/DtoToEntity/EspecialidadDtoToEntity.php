<?php

namespace App\Service\DtoToEntity;

use App\Dto\EspecialidadDto;
use App\Dto\ProvinciaDto;
use App\Entity\Especialidad;
use App\Repository\EspecialidadRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class EspecialidadDtoToEntity
{

    public function __construct(
        public EntityManagerInterface $em,
        public EspecialidadRepository $especialidadRepository,
        public CuerpoDtoToEntity $cuerpoDtoToEntity,
    ) {
    }

    public function get(EspecialidadDto $dto, bool $persist = true): Especialidad
    {
        $especialidad = $this->especialidadRepository->find($dto->id);

        if (!$especialidad) {
            $especialidad = new Especialidad(
                id: $dto->id,
                nombre: $dto->nombre,
                cuerpo: $this->cuerpoDtoToEntity->get($dto->cuerpo),
            );
            if ($persist) {
                $this->em->persist($especialidad);
            }
        }
        return $especialidad;
    }

}
