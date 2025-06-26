<?php

namespace App\Entity;

use App\Repository\AdjudicacionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdjudicacionRepository::class)]
class Adjudicacion
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $puesto = null;

    #[ORM\OneToOne(inversedBy: 'adjudicacion', cascade: ['persist', 'remove'])]
    private ?Plaza $plaza = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getPuesto(): ?int
    {
        return $this->puesto;
    }

    public function setPuesto(int $puesto): static
    {
        $this->puesto = $puesto;

        return $this;
    }

    public function getPlaza(): ?Plaza
    {
        return $this->plaza;
    }

    public function setPlaza(?Plaza $plaza): static
    {
        $this->plaza = $plaza;

        return $this;
    }
}
