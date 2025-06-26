<?php

namespace App\Entity;

use App\Repository\CentroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CentroRepository::class)]
class Centro
{
    public const array OCEP_OTROS_CENTROS = [
        '04989892', //Almería
        '11989895', //Cádiz
        '14989894', //Córdoba
        '18989891', //Granada
        '21989897', //Huelva
        '23989891', //Jaén
        '29989898', //Málaga
        '41989890', //Sevilla
    ];

    #[ORM\Id, ORM\Column]
    private ?string $id = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'centros')]
    private ?Localidad $localidad = null;

    /**
     * @var Collection<int, Plaza>
     */
    #[ORM\OneToMany(targetEntity: Plaza::class, mappedBy: 'centro')]
    private Collection $plazas;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    public function __construct(
        string $id,
        string $nombre,
        Localidad $localidad,
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->localidad = $localidad;

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

    public function getLocalidad(): ?Localidad
    {
        return $this->localidad;
    }

    public function setLocalidad(?Localidad $localidad): static
    {
        $this->localidad = $localidad;

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
            $plaza->setCentro($this);
        }

        return $this;
    }

    public function removePlaza(Plaza $plaza): static
    {
        if ($this->plazas->removeElement($plaza)) {
            // set the owning side to null (unless already changed)
            if ($plaza->getCentro() === $this) {
                $plaza->setCentro(null);
            }
        }

        return $this;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

}
