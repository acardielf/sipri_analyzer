<?php

namespace App\Service\DtoToEntity;

use App\Dto\ProvinciaDto;
use App\Entity\Provincia;
use App\Repository\ProvinciaRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class ProvinciaDtoToEntity
{

    public function __construct(
        public EntityManagerInterface $em,
        public ProvinciaRepository    $repository,
    )
    {
    }

    public function get(ProvinciaDto $dto, bool $persist = true): Provincia
    {
        $provincia = $this->repository->find($dto->id);

        if (!$provincia) {
            $provincia = new Provincia(
                id: $dto->id,
                nombre: $dto->nombre,
            );
            if ($persist) {
                $this->em->persist($provincia);
            }
        }
        return $provincia;
    }

}
