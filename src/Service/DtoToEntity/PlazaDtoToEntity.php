<?php

namespace App\Service\DtoToEntity;

use App\Dto\PlazaDto;
use App\Entity\Plaza;
use App\Repository\PlazaRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class PlazaDtoToEntity
{

    public function __construct(
        public EntityManagerInterface  $em,
        public PlazaRepository         $repository,
        public CentroDtoToEntity       $centroDtoToEntity,
        public EspecialidadDtoToEntity $especialidadDtoToEntity,
        public ConvocatoriaDtoToEntity $convocatoriaDtoToEntity,
        public LocalidadDtoToEntity    $localidadDtoToEntity,
    )
    {
    }

    public function get(PlazaDto $dto, bool $persist = true): ?object
    {
        $plaza = $this->repository->findByAttributes(
            convocatoriaId: $dto->convocatoria->id,
            centroId: $dto->centro->id,
            especialidadId: $dto->especialidad->id,
            tipo: $dto->tipoPlaza->value,
            obligatoriedad: $dto->obligatoriedadPlaza->value,
            fechaPrevistaCese: $dto->fechaPrevistaCese,
            numero: $dto->numero,
        );

        if (!$plaza) {
            $plaza = new Plaza(
                convocatoria: $this->convocatoriaDtoToEntity->get($dto->convocatoria),
                centro: $this->centroDtoToEntity->get($dto->centro),
                especialidad: $this->especialidadDtoToEntity->get($dto->especialidad),
                tipo: $dto->tipoPlaza,
                obligatoriedad: $dto->obligatoriedadPlaza,
                fechaPrevistaCese: $dto->fechaPrevistaCese,
                numero: $dto->numero,
            );
            if ($persist) {
                $this->em->persist($plaza);
            }
        }

        return $plaza;
    }

}
