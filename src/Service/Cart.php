<?php

namespace App\Service;

use App\Entity\HotelService;
use App\Entity\Room;
use App\Repository\HotelServiceRepository;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Panier de séjours stocké en session.
 *
 * Seuls des identifiants et des dates sont conservés : les prix sont TOUJOURS
 * recalculés côté serveur à partir de la base (le client ne peut pas modifier
 * un prix).
 */
class Cart
{
    private const KEY = 'booking_cart';
    public const MAX_ITEMS = 5;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RoomRepository $rooms,
        private readonly HotelServiceRepository $services,
        private readonly ReservationRepository $reservations,
    ) {
    }

    /** @param HotelService[] $extras */
    public function quote(Room $room, \DateTimeImmutable $in, \DateTimeImmutable $out, int $guests, iterable $extras): array
    {
        $nights = (int) $in->diff($out)->days;
        $roomTotal = $room->getPricePerNight() * $nights;
        $lines = [];
        $extrasTotal = 0.0;
        foreach ($extras as $extra) {
            $price = $extra->computePrice($nights, $guests);
            $lines[] = ['service' => $extra, 'price' => $price];
            $extrasTotal += $price;
        }

        return [
            'nights' => $nights,
            'roomTotal' => $roomTotal,
            'extras' => $lines,
            'extrasTotal' => $extrasTotal,
            'total' => round($roomTotal + $extrasTotal, 2),
        ];
    }

    /**
     * @param HotelService[] $extras
     * @return string|null message d'erreur, ou null si l'ajout a réussi
     */
    public function add(Room $room, \DateTimeImmutable $in, \DateTimeImmutable $out, int $guests, iterable $extras, ?string $request): ?string
    {
        $items = $this->raw();
        if (count($items) >= self::MAX_ITEMS) {
            return sprintf('Votre panier est limité à %d séjours.', self::MAX_ITEMS);
        }
        if ($guests > $room->getCapacity()) {
            return sprintf('Cette chambre accueille au maximum %d personne(s).', $room->getCapacity());
        }
        if (!$this->reservations->isRoomAvailable($room, $in, $out)) {
            return "Désolé, cette chambre n'est plus disponible sur ces dates.";
        }
        foreach ($items as $item) {
            if ($item['room'] === $room->getId()
                && $item['in'] < $out->format('Y-m-d') && $item['out'] > $in->format('Y-m-d')) {
                return 'Ce séjour chevauche un séjour déjà présent dans votre panier.';
            }
        }

        $extraIds = [];
        foreach ($extras as $extra) {
            $extraIds[] = $extra->getId();
        }

        $items[bin2hex(random_bytes(6))] = [
            'room' => $room->getId(),
            'in' => $in->format('Y-m-d'),
            'out' => $out->format('Y-m-d'),
            'guests' => $guests,
            'extras' => $extraIds,
            'request' => $request ? mb_substr($request, 0, 500) : null,
        ];
        $this->save($items);

        return null;
    }

    public function remove(string $key): void
    {
        $items = $this->raw();
        unset($items[$key]);
        $this->save($items);
    }

    public function clear(): void
    {
        $this->save([]);
    }

    public function count(): int
    {
        return count($this->raw());
    }

    /**
     * Lignes du panier "hydratées" avec les entités et les prix recalculés.
     *
     * @return array<string, array{key: string, room: Room, checkIn: \DateTimeImmutable, checkOut: \DateTimeImmutable, guests: int, extrasEntities: HotelService[], request: ?string, quote: array, available: bool}>
     */
    public function lines(): array
    {
        $lines = [];
        $changed = false;
        $items = $this->raw();
        foreach ($items as $key => $item) {
            $room = $this->rooms->find($item['room']);
            $in = \DateTimeImmutable::createFromFormat('!Y-m-d', $item['in']);
            $out = \DateTimeImmutable::createFromFormat('!Y-m-d', $item['out']);
            if (!$room || !$room->isActive() || !$in || !$out || $in < new \DateTimeImmutable('today')) {
                unset($items[$key]);
                $changed = true;
                continue;
            }
            $extras = $item['extras'] ? $this->services->findBy(['id' => $item['extras'], 'isActive' => true]) : [];
            $lines[$key] = [
                'key' => $key,
                'room' => $room,
                'checkIn' => $in,
                'checkOut' => $out,
                'guests' => (int) $item['guests'],
                'extrasEntities' => $extras,
                'request' => $item['request'] ?? null,
                'quote' => $this->quote($room, $in, $out, (int) $item['guests'], $extras),
                'available' => $this->reservations->isRoomAvailable($room, $in, $out),
            ];
        }
        if ($changed) {
            $this->save($items);
        }

        return $lines;
    }

    public function total(?array $lines = null): float
    {
        return array_sum(array_map(fn ($l) => $l['quote']['total'], $lines ?? $this->lines()));
    }

    private function raw(): array
    {
        return $this->requestStack->getSession()->get(self::KEY, []);
    }

    private function save(array $items): void
    {
        $this->requestStack->getSession()->set(self::KEY, $items);
    }
}
