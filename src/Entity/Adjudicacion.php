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
    private ?int $orden = null;

    #[ORM\ManyToOne(inversedBy: 'adjudicaciones')]
    private ?Plaza $plaza = null;


    /**
     * @param int|null $id
     * @param int|null $puesto
     */
    public function __construct(?int $id, ?int $puesto, ?Plaza $plaza = null)
    {
        $this->id = $id;
        $this->orden = $puesto;
        $this->plaza = $plaza;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getOrden(): ?int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): static
    {
        $this->orden = $orden;

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
