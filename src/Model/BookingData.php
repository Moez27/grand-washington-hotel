<?php

namespace App\Model;

use App\Entity\HotelService;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Données du formulaire "Réserver cette chambre" (DTO, non persisté).
 */
class BookingData
{
    #[Assert\NotNull(message: "Indiquez la date d'arrivée.")]
    #[Assert\GreaterThanOrEqual('today', message: "La date d'arrivée ne peut pas être dans le passé.")]
    public ?\DateTimeImmutable $checkIn = null;

    #[Assert\NotNull(message: 'Indiquez la date de départ.')]
    #[Assert\GreaterThan(propertyPath: 'checkIn', message: "La date de départ doit être après la date d'arrivée.")]
    public ?\DateTimeImmutable $checkOut = null;

    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 12)]
    public ?int $guests = 1;

    /** @var HotelService[] */
    public iterable $extras = [];

    #[Assert\Length(max: 500)]
    public ?string $specialRequest = null;

    #[Assert\Callback]
    public function validateLength(ExecutionContextInterface $context): void
    {
        if ($this->checkIn && $this->checkOut && $this->checkIn->diff($this->checkOut)->days > 30) {
            $context->buildViolation('Un séjour ne peut pas dépasser 30 nuits.')
                ->atPath('checkOut')->addViolation();
        }
    }
}
