<?php

namespace App\Entity;

use App\Repository\ConvocatoriaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConvocatoriaRepository::class)]
class Convocatoria
{
    use ConvocatoriaConfigurationTrait;

    #[ORM\Id, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 12)]
    private ?string $nombre = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'convocatorias')]
    private ?Curso $curso = null;

    /**
     * @var Collection<int, Plaza>
     */
    #[ORM\OneToMany(targetEntity: Plaza::class, mappedBy: 'convocatoria')]
    private Collection $plazas;

    public function __construct(
        int    $id,
        string $nombre,
        Curso  $curso,
    )
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->curso = $curso;
        $this->plazas = new ArrayCollection();
    }

    public static function isNaturalOrder(int $convocatoria): bool
    {
        return !in_array($convocatoria, static::CONVOCATORIA_ORDEN_ALTERNATIVO, true);
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

    public function getCurso(): ?Curso
    {
        return $this->curso;
    }

    public function setCurso(?Curso $curso): static
    {
        $this->curso = $curso;

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
            $plaza->setConvocatoria($this);
        }

        return $this;
    }

    public function removePlaza(Plaza $plaza): static
    {
        if ($this->plazas->removeElement($plaza)) {
            // set the owning side to null (unless already changed)
            if ($plaza->getConvocatoria() === $this) {
                $plaza->setConvocatoria(null);
            }
        }

        return $this;
    }
}
