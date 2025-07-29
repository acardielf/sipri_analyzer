<?php

namespace App\Service\DtoToEntity;

use App\Dto\CuerpoDto;
use App\Entity\Cuerpo;
use App\Repository\CuerpoRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class CuerpoDtoToEntity
{

    public function __construct(
        public EntityManagerInterface $em,
        public CuerpoRepository $repository,
    ) {
    }

    public function get(CuerpoDto $dto): Cuerpo
    {
        $cuerpo = $this->repository->find($dto->id);

        if (!$cuerpo) {
            $cuerpo = $this->repository->find("999");
        }

        return $cuerpo;
    }

}
