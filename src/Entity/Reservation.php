<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Index(name: 'idx_reservation_dates', columns: ['room_id', 'check_in', 'check_out'])]
class Reservation
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_PENDING => 'En attente',
        self::STATUS_CONFIRMED => 'Confirmée',
        self::STATUS_COMPLETED => 'Terminée',
        self::STATUS_CANCELLED => 'Annulée',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING => 'warning',
        self::STATUS_CONFIRMED => 'success',
        self::STATUS_COMPLETED => 'info',
        self::STATUS_CANCELLED => 'danger',
    ];

    /** Statuts qui bloquent la chambre sur la période */
    public const BLOCKING_STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMED];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Room $room = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $checkIn = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    #[Assert\GreaterThan(propertyPath: 'checkIn', message: "La date de départ doit être après la date d'arrivée.")]
    private ?\DateTimeImmutable $checkOut = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: 12)]
    private int $guests = 1;

    /** @var Collection<int, HotelService> */
    #[ORM\ManyToMany(targetEntity: HotelService::class)]
    private Collection $extras;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPrice = '0';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_CANCELLED, self::STATUS_COMPLETED])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    private ?string $specialRequest = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->extras = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->reference = 'GW-'.strtoupper(bin2hex(random_bytes(4)));
    }

    public function getId(): ?int { return $this->id; }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(string $reference): static { $this->reference = $reference; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getRoom(): ?Room { return $this->room; }
    public function setRoom(?Room $room): static { $this->room = $room; return $this; }

    public function getCheckIn(): ?\DateTimeImmutable { return $this->checkIn; }
    public function setCheckIn(?\DateTimeImmutable $checkIn): static { $this->checkIn = $checkIn; return $this; }

    public function getCheckOut(): ?\DateTimeImmutable { return $this->checkOut; }
    public function setCheckOut(?\DateTimeImmutable $checkOut): static { $this->checkOut = $checkOut; return $this; }

    public function getNights(): int
    {
        if (!$this->checkIn || !$this->checkOut) {
            return 0;
        }
        return max(0, (int) $this->checkIn->diff($this->checkOut)->days);
    }

    public function getGuests(): int { return $this->guests; }
    public function setGuests(int $guests): static { $this->guests = $guests; return $this; }

    /** @return Collection<int, HotelService> */
    public function getExtras(): Collection { return $this->extras; }
    public function addExtra(HotelService $extra): static { if (!$this->extras->contains($extra)) { $this->extras->add($extra); } return $this; }
    public function removeExtra(HotelService $extra): static { $this->extras->removeElement($extra); return $this; }

    public function getTotalPrice(): float { return (float) $this->totalPrice; }
    public function setTotalPrice(float|string $totalPrice): static { $this->totalPrice = (string) $totalPrice; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    public function getStatusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }
    public function getStatusColor(): string { return self::STATUS_COLORS[$this->status] ?? 'secondary'; }

    /** Le client peut annuler une réservation non encore commencée et non annulée */
    public function isCancellable(): bool
    {
        return in_array($this->status, self::BLOCKING_STATUSES, true)
            && $this->checkIn > new \DateTimeImmutable('today');
    }

    public function getSpecialRequest(): ?string { return $this->specialRequest; }
    public function setSpecialRequest(?string $specialRequest): static { $this->specialRequest = $specialRequest; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
