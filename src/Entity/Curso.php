<?php

namespace App\Entity;

use App\Repository\CursoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CursoRepository::class)]
class Curso
{
    #[ORM\Id]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 9)]
    private ?string $nombre = null;

    #[ORM\Column(length: 4)]
    private ?string $simple = null;

    /**
     * @var Collection<int, Convocatoria>
     */
    #[ORM\OneToMany(targetEntity: Convocatoria::class, mappedBy: 'curso')]
    private Collection $convocatorias;

    public function __construct(
        int    $id,
        string $nombre,
        string $simple,
    )
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->simple = $simple;
        $this->convocatorias = new ArrayCollection();
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

    public function getSimple(): ?string
    {
        return $this->simple;
    }

    public function setSimple(string $simple): static
    {
        $this->simple = $simple;

        return $this;
    }

    /**
     * @return Collection<int, Convocatoria>
     */
    public function getConvocatorias(): Collection
    {
        return $this->convocatorias;
    }

    public function addConvocatoria(Convocatoria $convocatoria): static
    {
        if (!$this->convocatorias->contains($convocatoria)) {
            $this->convocatorias->add($convocatoria);
            $convocatoria->setCurso($this);
        }

        return $this;
    }

    public function removeConvocatoria(Convocatoria $convocatoria): static
    {
        if ($this->convocatorias->removeElement($convocatoria)) {
            // set the owning side to null (unless already changed)
            if ($convocatoria->getCurso() === $this) {
                $convocatoria->setCurso(null);
            }
        }

        return $this;
    }
}
