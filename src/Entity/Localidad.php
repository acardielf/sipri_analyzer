<?php

namespace App\Entity;

use App\Repository\LocalidadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocalidadRepository::class)]
class Localidad
{
    #[ORM\Id]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'localidades')]
    private ?Provincia $provincia = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    /**
     * @var Collection<int, Centro>
     */
    #[ORM\OneToMany(targetEntity: Centro::class, mappedBy: 'localidad')]
    private Collection $centros;

    public function __construct(
        int       $id,
        string    $nombre,
        Provincia $provincia,
    )
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->provincia = $provincia;
        $this->centros = new ArrayCollection();
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

    public function getProvincia(): ?Provincia
    {
        return $this->provincia;
    }

    public function setProvincia(?Provincia $provincia): static
    {
        $this->provincia = $provincia;

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
     * @return Collection<int, Centro>
     */
    public function getCentros(): Collection
    {
        return $this->centros;
    }

    public function addCentro(Centro $centro): static
    {
        if (!$this->centros->contains($centro)) {
            $this->centros->add($centro);
            $centro->setLocalidad($this);
        }

        return $this;
    }

    public function removeCentro(Centro $centro): static
    {
        if ($this->centros->removeElement($centro)) {
            // set the owning side to null (unless already changed)
            if ($centro->getLocalidad() === $this) {
                $centro->setLocalidad(null);
            }
        }

        return $this;
    }
}
