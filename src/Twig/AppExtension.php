<?php

namespace App\Twig;

use App\Repository\ReservationRepository;
use App\Repository\ReviewRepository;
use App\Entity\Reservation;
use App\Service\Cart;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly Cart $cart,
        private readonly ReservationRepository $reservations,
        private readonly ReviewRepository $reviews,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_count', fn () => $this->cart->count()),
            new TwigFunction('admin_pending_count', fn () => $this->reservations->count(['status' => Reservation::STATUS_PENDING])),
            new TwigFunction('admin_pending_reviews', fn () => $this->reviews->countPending()),
        ];
    }
}
