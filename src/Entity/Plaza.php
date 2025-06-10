<?php

namespace App\Entity;

use App\Enum\ObligatoriedadPlazaEnum;
use App\Enum\TipoPlazaEnum;
use App\Repository\PlazaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlazaRepository::class)]
class Plaza
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'plazas')]
    private ?Convocatoria $convocatoria = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'plazas')]
    private ?Centro $centro = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'plazas')]
    private ?Especialidad $especialidad = null;

    #[ORM\Column(enumType: TipoPlazaEnum::class)]
    private ?TipoPlazaEnum $tipo = null;

    #[ORM\Column(enumType: ObligatoriedadPlazaEnum::class)]
    private ?ObligatoriedadPlazaEnum $obligatoriedad = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $fecha_prevista_cese = null;

    #[ORM\Column]
    private ?int $numero = null;


    public function __construct(
        Convocatoria $convocatoria,
        Centro $centro,
        Especialidad $especialidad,
        TipoPlazaEnum $tipo,
        ObligatoriedadPlazaEnum $obligatoriedad,
        ?\DateTime $fecha_prevista_cese = null,
        int $numero = 0
    )
    {
        $this->convocatoria = $convocatoria;
        $this->centro = $centro;
        $this->especialidad = $especialidad;
        $this->tipo = $tipo;
        $this->obligatoriedad = $obligatoriedad;
        $this->fecha_prevista_cese = $fecha_prevista_cese;
        $this->numero = $numero;
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

    public function getFechaPrevistaCese(): ?\DateTime
    {
        return $this->fecha_prevista_cese;
    }

    public function setFechaPrevistaCese(?\DateTime $fecha_prevista_cese): static
    {
        $this->fecha_prevista_cese = $fecha_prevista_cese;

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
}
