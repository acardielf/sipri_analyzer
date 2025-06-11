<?php

namespace App\Entity;

use App\Repository\ProvinciaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProvinciaRepository::class)]
class Provincia
{
    #[ORM\Id]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 7)]
    private ?string $nombre = null;

    /**
     * @var Collection<int, Localidad>
     */
    #[ORM\OneToMany(targetEntity: Localidad::class, mappedBy: 'provincia')]
    private Collection $localidades;

    public function __construct(
        int    $id = 0,
        string $nombre = ''
    )
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->localidades = new ArrayCollection();
    }

    public static function fromString(string $name): Provincia
    {
        $provincia = new Provincia();
        $values = explode(' - ', $name, 2);
        $provincia->setId($values[0]);
        $provincia->setNombre($values[1]);

        return $provincia;
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
     * @return Collection<int, Localidad>
     */
    public function getLocalidades(): Collection
    {
        return $this->localidades;
    }

    public function addLocalidade(Localidad $localidade): static
    {
        if (!$this->localidades->contains($localidade)) {
            $this->localidades->add($localidade);
            $localidade->setProvincia($this);
        }

        return $this;
    }

    public function removeLocalidade(Localidad $localidade): static
    {
        if ($this->localidades->removeElement($localidade)) {
            // set the owning side to null (unless already changed)
            if ($localidade->getProvincia() === $this) {
                $localidade->setProvincia(null);
            }
        }

        return $this;
    }
}
