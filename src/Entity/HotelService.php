<?php

namespace App\Entity;

use App\Repository\HotelServiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/** Service additionnel réservable avec une chambre : spa, petit-déjeuner, navette... */
#[ORM\Entity(repositoryClass: HotelServiceRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Ce service existe déjà.')]
class HotelService
{
    public const PRICING_PER_STAY = 'per_stay';
    public const PRICING_PER_NIGHT = 'per_night';
    public const PRICING_PER_PERSON_NIGHT = 'per_person_night';

    public const PRICING_LABELS = [
        self::PRICING_PER_STAY => 'par séjour',
        self::PRICING_PER_NIGHT => 'par nuit',
        self::PRICING_PER_PERSON_NIGHT => 'par personne / nuit',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?string $price = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::PRICING_PER_STAY, self::PRICING_PER_NIGHT, self::PRICING_PER_PERSON_NIGHT])]
    private string $pricingType = self::PRICING_PER_STAY;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^bi-[a-z0-9\-]+$/', message: 'Icône Bootstrap invalide (ex : bi-cup-hot).')]
    private string $icon = 'bi-stars';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private bool $isActive = true;

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): static { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getPrice(): ?float { return $this->price !== null ? (float) $this->price : null; }
    public function setPrice(float|string|null $price): static { $this->price = $price !== null ? (string) $price : null; return $this; }

    public function getPricingType(): string { return $this->pricingType; }
    public function setPricingType(string $pricingType): static { $this->pricingType = $pricingType; return $this; }
    public function getPricingLabel(): string { return self::PRICING_LABELS[$this->pricingType] ?? ''; }

    /** Prix de ce service pour un séjour donné */
    public function computePrice(int $nights, int $guests): float
    {
        return match ($this->pricingType) {
            self::PRICING_PER_NIGHT => $this->getPrice() * $nights,
            self::PRICING_PER_PERSON_NIGHT => $this->getPrice() * $nights * $guests,
            default => (float) $this->getPrice(),
        };
    }

    public function getIcon(): string { return $this->icon; }
    public function setIcon(?string $icon): static { $this->icon = (string) $icon; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function __toString(): string { return (string) $this->name; }
}
