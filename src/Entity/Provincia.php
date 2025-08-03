<?php

namespace App\Entity;

use App\Repository\ProvinciaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProvinciaRepository::class)]
class Provincia
{
    #[ORM\Id, ORM\Column]
    private ?string $id;

    #[ORM\Column(length: 7)]
    private ?string $nombre;

    /**
     * @var Collection<int, Localidad>
     */
    #[ORM\OneToMany(targetEntity: Localidad::class, mappedBy: 'provincia')]
    private Collection $localidades;

    public function __construct(
        string $id,
        string $nombre = ''
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->localidades = new ArrayCollection();
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
     * @return Collection<int, Localidad>
     */
    public function getLocalidades(): Collection
    {
        return $this->localidades;
    }

    public function addLocalidad(Localidad $localidad): static
    {
        if (!$this->localidades->contains($localidad)) {
            $this->localidades->add($localidad);
            $localidad->setProvincia($this);
        }

        return $this;
    }

    public function removeLocalidad(Localidad $localidad): static
    {
        if ($this->localidades->removeElement($localidad)) {
            // set the owning side to null (unless already changed)
            if ($localidad->getProvincia() === $this) {
                $localidad->setProvincia(null);
            }
        }

        return $this;
    }

    public function getTwoLetterCode(): string
    {
        return match ($this->id) {
            '04', '4' => 'AL',
            '11' => 'CA',
            '14' => 'CO',
            '18' => 'GR',
            '21' => 'HU',
            '23' => 'JA',
            '29' => 'MA',
            '41' => 'SE',
            default => $this->id,
        };
    }
}
