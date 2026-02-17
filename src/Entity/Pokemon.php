<?php

namespace App\Entity;

use App\Repository\PokemonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PokemonRepository::class)]
class Pokemon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'text')]
    private ?string $descripcion = null;

    #[ORM\Column(length: 255)]
    private ?string $imagen = null;

    #[ORM\Column(length: 255)]
    private ?string $tipo = null;

    #[ORM\ManyToOne(inversedBy: 'pokemon')]
    private ?User $entrenador = null;

    // --- CORRECCIÓN AQUÍ ---
    // Usamos una sola variable.
    // 'name' conecta con tu base de datos (fecha_creacion)
    // $fechaCreacion es para usarlo en PHP
    #[ORM\Column(name: 'fecha_creacion', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaCreacion = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favoritos')]
    private Collection $fans;

    public function __construct()
    {
        $this->fans = new ArrayCollection();
        // Inicializamos la fecha al crear el objeto para evitar errores de NULL
        $this->fechaCreacion = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    public function setImagen(string $imagen): static
    {
        $this->imagen = $imagen;

        return $this;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }

    // --- GETTER Y SETTER DE LA FECHA (Solo una vez) ---
    public function getFechaCreacion(): ?\DateTimeImmutable
    {
        return $this->fechaCreacion;
    }

    public function setFechaCreacion(\DateTimeImmutable $fechaCreacion): static
    {
        $this->fechaCreacion = $fechaCreacion;

        return $this;
    }

    public function getEntrenador(): ?User
    {
        return $this->entrenador;
    }

    public function setEntrenador(?User $entrenador): static
    {
        $this->entrenador = $entrenador;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFans(): Collection
    {
        return $this->fans;
    }

    public function addFan(User $fan): static
    {
        if (!$this->fans->contains($fan)) {
            $this->fans->add($fan);
            $fan->addFavorito($this);
        }

        return $this;
    }

    public function removeFan(User $fan): static
    {
        if ($this->fans->removeElement($fan)) {
            $fan->removeFavorito($this);
        }

        return $this;
    }
}