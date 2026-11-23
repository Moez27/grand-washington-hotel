<?php

namespace App\Entity;

use App\Repository\AmenityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/** Équipement d'une chambre : Wi-Fi, Climatisation, Vue mer... */
#[ORM\Entity(repositoryClass: AmenityRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Cet équipement existe déjà.')]
class Amenity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 60)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^bi-[a-z0-9\-]+$/', message: 'Icône Bootstrap invalide (ex : bi-wifi).')]
    private string $icon = 'bi-check2-circle';

    /** @var Collection<int, Room> */
    #[ORM\ManyToMany(targetEntity: Room::class, mappedBy: 'amenities')]
    private Collection $rooms;

    public function __construct() { $this->rooms = new ArrayCollection(); }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): static { $this->name = $name; return $this; }

    public function getIcon(): string { return $this->icon; }
    public function setIcon(?string $icon): static { $this->icon = (string) $icon; return $this; }

    /** @return Collection<int, Room> */
    public function getRooms(): Collection { return $this->rooms; }

    public function __toString(): string { return (string) $this->name; }
}
