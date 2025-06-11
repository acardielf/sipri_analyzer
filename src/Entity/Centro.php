<?php

namespace App\Entity;

use App\Repository\CentroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CentroRepository::class)]
class Centro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

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
        int       $id,
        string    $nombre,
        Localidad $localidad,
    )
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->localidad = $localidad;

        $this->plazas = new ArrayCollection();
    }

    public static function fromString(string $centro, string $localidad, string $provincia): Centro
    {
        $object = new Centro();
        $values = explode(' - ', $centro, 2);
        $object->setId($values[0]);
        $object->setNombre($values[1]);
        $object->setLocalidad(Localidad::fromString($localidad, $provincia));

        return $object;
    }

    public function getId(): ?int
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
