<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/** Catégorie de chambre : Standard, Deluxe, Suite... */
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Cette catégorie existe déjà.')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 80)]
    private ?string $name = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^bi-[a-z0-9\-]+$/', message: 'Icône Bootstrap invalide (ex : bi-star).')]
    private string $icon = 'bi-door-open';

    /** @var Collection<int, Room> */
    #[ORM\OneToMany(targetEntity: Room::class, mappedBy: 'category')]
    private Collection $rooms;

    public function __construct() { $this->rooms = new ArrayCollection(); }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): static { $this->name = $name; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getIcon(): string { return $this->icon; }
    public function setIcon(?string $icon): static { $this->icon = (string) $icon; return $this; }

    /** @return Collection<int, Room> */
    public function getRooms(): Collection { return $this->rooms; }

    public function __toString(): string { return (string) $this->name; }
}
