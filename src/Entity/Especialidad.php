<?php

namespace App\Entity;

use App\Repository\EspecialidadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EspecialidadRepository::class)]
class Especialidad
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue(strategy: 'NONE')]
    private ?string $id;

    #[ORM\Column(length: 255)]
    private ?string $nombre;

    /**
     * @var Collection<int, Plaza>
     */
    #[ORM\OneToMany(targetEntity: Plaza::class, mappedBy: 'especialidad')]
    private Collection $plazas;

    #[ORM\ManyToOne(targetEntity: Cuerpo::class, inversedBy: 'especialidades')]
    private ?Cuerpo $cuerpo = null;



    public function __construct(
        string $id,
        string $nombre,
        ?Cuerpo $cuerpo = null
    )
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->cuerpo = $cuerpo;
        $this->plazas = new ArrayCollection();
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

    /**
     * @return Collection<int, Plaza>
     */
    public function getPlazas(): Collection
    {
        return $this->plazas;
    }

    public function addPlaza(Plaza $plaza): static
    {
        if (!$this->plazas->contains($plaza)) {
            $this->plazas->add($plaza);
            $plaza->setEspecialidad($this);
        }

        return $this;
    }

    public function removePlaza(Plaza $plaza): static
    {
        if ($this->plazas->removeElement($plaza)) {
            // set the owning side to null (unless already changed)
            if ($plaza->getEspecialidad() === $this) {
                $plaza->setEspecialidad(null);
            }
        }

        return $this;
    }

    public function getCuerpo(): ?Cuerpo
    {
        return $this->cuerpo;
    }
}
