<?php

namespace App\Entity;

use App\Enum\ObligatoriedadPlazaEnum;
use App\Enum\TipoPlazaEnum;
use App\Repository\PlazaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlazaRepository::class)]
class Plaza
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'plazas')]
    private ?Convocatoria $convocatoria = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'plazas')]
    private ?Centro $centro = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'plazas')]
    private ?Especialidad $especialidad = null;

    /**
     * @var Collection<int, Adjudicacion>
     */
    #[ORM\OneToMany(targetEntity: Adjudicacion::class, mappedBy: 'plaza', cascade: ['persist', 'remove'])]
    private Collection $adjudicaciones;

    #[ORM\Column(enumType: TipoPlazaEnum::class)]
    private ?TipoPlazaEnum $tipo = null;

    #[ORM\Column(enumType: ObligatoriedadPlazaEnum::class)]
    private ?ObligatoriedadPlazaEnum $obligatoriedad = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $fechaPrevistaCese = null;

    #[ORM\Column]
    private ?int $numero;

    #[ORM\Column]
    private int $ocurrencia;

    #[ORM\Column]
    private string $hash;


    public function __construct(
        Convocatoria $convocatoria,
        Centro $centro,
        Especialidad $especialidad,
        TipoPlazaEnum $tipo,
        ObligatoriedadPlazaEnum $obligatoriedad,
        ?\DateTimeImmutable $fechaPrevistaCese = null,
        int $numero = 0,
        int $ocurrencia = 1,
    ) {
        $this->convocatoria = $convocatoria;
        $this->centro = $centro;
        $this->especialidad = $especialidad;
        $this->tipo = $tipo;
        $this->obligatoriedad = $obligatoriedad;
        $this->fechaPrevistaCese = $fechaPrevistaCese;
        $this->numero = $numero;
        $this->ocurrencia = $ocurrencia;

        $this->hash = hash(
            'sha256',
            $convocatoria->getId() .
            $centro->getId() .
            $especialidad->getId() .
            $tipo->value .
            $obligatoriedad->value .
            ($fechaPrevistaCese ? $fechaPrevistaCese->format('Y-m-d') : '') .
            $numero .
            $ocurrencia
        );
        $this->adjudicaciones = new ArrayCollection();
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

    public function getConvocatoria(): ?Convocatoria
    {
        return $this->convocatoria;
    }

    public function setConvocatoria(?Convocatoria $convocatoria): static
    {
        $this->convocatoria = $convocatoria;

        return $this;
    }

    public function getCentro(): ?Centro
    {
        return $this->centro;
    }

    public function setCentro(?Centro $centro): static
    {
        $this->centro = $centro;

        return $this;
    }

    public function getEspecialidad(): ?Especialidad
    {
        return $this->especialidad;
    }

    public function setEspecialidad(?Especialidad $especialidad): static
    {
        $this->especialidad = $especialidad;

        return $this;
    }

    public function getTipo(): ?TipoPlazaEnum
    {
        return $this->tipo;
    }

    public function setTipo(TipoPlazaEnum $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function getObligatoriedad(): ?ObligatoriedadPlazaEnum
    {
        return $this->obligatoriedad;
    }

    public function setObligatoriedad(ObligatoriedadPlazaEnum $obligatoriedad): static
    {
        $this->obligatoriedad = $obligatoriedad;

        return $this;
    }

    public function getFechaPrevistaCese(): ?\DateTimeImmutable
    {
        return $this->fechaPrevistaCese;
    }

    public function setFechaPrevistaCese(?\DateTimeImmutable $fechaPrevistaCese): static
    {
        $this->fechaPrevistaCese = $fechaPrevistaCese;

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getOcurrencia(): int
    {
        return $this->ocurrencia;
    }

    public function setOcurrencia(int $ocurrencia): void
    {
        $this->ocurrencia = $ocurrencia;
    }

    /**
     * @return Collection<int, Adjudicacion>
     */
    public function getAdjudicaciones(): Collection
    {
        return $this->adjudicaciones;
    }

    public function getAdjudicacionesAsString(): string
    {
        return implode(', ', $this->adjudicaciones->map(fn(Adjudicacion $a) => (string)$a->getOrden())->toArray());
    }


    public function adjudicadaCompletamente(): bool
    {
        return count($this->getAdjudicaciones()) >= $this->numero;
    }

    public function addAdjudicacion(Adjudicacion $adjudicacion): static
    {
        if (count($this->getAdjudicaciones()) > $this->numero) {
            throw new \LogicException('No se pueden añadir más adjudicaciones a esta plaza. Límite alcanzado.');
        }

        if (!$this->adjudicaciones->contains($adjudicacion)) {
            $this->adjudicaciones->add($adjudicacion);
            $adjudicacion->setPlaza($this);
        }

        return $this;
    }

    public function removeAdjudicacion(Adjudicacion $adjudicacion): static
    {
        if ($this->adjudicaciones->removeElement($adjudicacion)) {
            // set the owning side to null (unless already changed)
            if ($adjudicacion->getPlaza() === $this) {
                $adjudicacion->setPlaza(null);
            }
        }

        return $this;
    }

    public function hasAtLeastOneAdjudicacion(): bool
    {
        return !$this->adjudicaciones->isEmpty();
    }

    public function isDesierta(): bool
    {
        return $this->convocatoria->hasAdjudicacion() && count($this->getAdjudicaciones()) == 0;
    }


}
