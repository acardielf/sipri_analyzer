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
    private ?string $id;

    #[ORM\Column(length: 12)]
    private ?string $nombre;


    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $fecha;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'convocatorias',)]
    private ?Curso $curso;

    /**
     * @var Collection<int, Plaza>
     */
    #[ORM\OneToMany(targetEntity: Plaza::class, mappedBy: 'convocatoria', cascade: ['persist'])]
    private Collection $plazas;

    public function __construct(
        string $id,
        string $nombre,
        ?\DateTimeImmutable $fecha,
        Curso $curso,
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->curso = $curso;
        $this->fecha = $fecha;
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

    public function getFecha(): ?\DateTimeImmutable
    {
        return $this->fecha;
    }

    public function setFecha(?\DateTimeImmutable $fecha): void
    {
        $this->fecha = $fecha;
    }

}

