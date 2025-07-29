<?php

namespace App\Entity;

use App\Repository\CuerpoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CuerpoRepository::class)]
class Cuerpo
{
    #[ORM\Id, ORM\Column]
    private ?string $id;

    #[ORM\Column(length: 50)]
    private ?string $nombre;

    /**
     * @var Collection<int, Especialidad>
     */
    #[ORM\OneToMany(targetEntity: Especialidad::class, mappedBy: 'cuerpo')]
    private Collection $especialidades;


    public function __construct(
        string $id,
        string $nombre
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->especialidades = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getEspecialidades(): Collection
    {
        return $this->especialidades;
    }

    public function setEspecialidades(Collection $especialidades): void
    {
        $this->especialidades = $especialidades;
    }



}
