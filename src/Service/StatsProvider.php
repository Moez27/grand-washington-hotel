<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;

/**
 * Calcule les indicateurs et séries de données du tableau de bord.
 */
class StatsProvider
{
    private const MONTHS_FR = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

    public function __construct(
        private readonly ReservationRepository $reservations,
        private readonly RoomRepository $rooms,
        private readonly UserRepository $users,
    ) {
    }

    public function dashboard(): array
    {
        $today = new \DateTimeImmutable('today');
        $start = $today->modify('first day of this month')->modify('-11 months');
        $rows = $this->reservations->statsRows($start->modify('-1 month'));

        // 12 derniers mois
        $months = [];
        for ($i = 0; $i < 12; ++$i) {
            $m = $start->modify("+$i months");
            $months[$m->format('Y-m')] = [
                'label' => self::MONTHS_FR[(int) $m->format('n') - 1].' '.$m->format('y'),
                'revenue' => 0.0,
                'bookings' => 0,
            ];
        }

        $revenueTotal = 0.0;
        $revenueThisMonth = 0.0;
        $revenueLastMonth = 0.0;
        $byCategory = [];
        $byRoom = [];
        $nightsThisMonth = 0;
        $thisMonth = $today->format('Y-m');
        $lastMonth = $today->modify('first day of last month')->format('Y-m');
        $monthStart = $today->modify('first day of this month');
        $monthEnd = $monthStart->modify('+1 month');

        foreach ($rows as $r) {
            $isRevenue = in_array($r['status'], [Reservation::STATUS_CONFIRMED, Reservation::STATUS_COMPLETED], true);
            $key = $r['checkIn']->format('Y-m');
            $price = (float) $r['totalPrice'];

            if (isset($months[$key]) && Reservation::STATUS_CANCELLED !== $r['status']) {
                ++$months[$key]['bookings'];
            }
            if (!$isRevenue) {
                continue;
            }
            if (isset($months[$key])) {
                $months[$key]['revenue'] += $price;
                $revenueTotal += $price;
            }
            if ($key === $thisMonth) {
                $revenueThisMonth += $price;
            } elseif ($key === $lastMonth) {
                $revenueLastMonth += $price;
            }
            $byCategory[$r['categoryName']] = ($byCategory[$r['categoryName']] ?? 0) + $price;
            $byRoom[$r['roomName']] = ($byRoom[$r['roomName']] ?? 0) + 1;

            // Nuits occupées ce mois-ci (pour le taux d'occupation)
            $from = max($r['checkIn'], $monthStart);
            $to = min($r['checkOut'], $monthEnd);
            if ($to > $from) {
                $nightsThisMonth += (int) $from->diff($to)->days;
            }
        }

        arsort($byRoom);
        $byRoom = array_slice($byRoom, 0, 6, true);
        arsort($byCategory);

        $activeRooms = $this->rooms->count(['isActive' => true]);
        $daysInMonth = (int) $today->format('t');
        $occupancy = $activeRooms > 0 ? round(100 * $nightsThisMonth / ($activeRooms * $daysInMonth), 1) : 0;

        // Inscriptions clients par mois
        $signups = array_fill_keys(array_keys($months), 0);
        foreach ($this->users->registrationDates($start) as $d) {
            $k = $d->format('Y-m');
            if (isset($signups[$k])) {
                ++$signups[$k];
            }
        }

        $statusCounts = $this->reservations->countByStatus();
        $totalBookings = array_sum($statusCounts);

        return [
            'kpi' => [
                'revenueTotal' => $revenueTotal,
                'revenueThisMonth' => $revenueThisMonth,
                'revenueTrend' => $revenueLastMonth > 0 ? round(100 * ($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth, 1) : null,
                'bookings' => $totalBookings,
                'pending' => $statusCounts[Reservation::STATUS_PENDING],
                'clients' => $this->users->countClients(),
                'rooms' => $activeRooms,
                'occupancy' => min(100, $occupancy),
                'averageBasket' => $totalBookings > 0 ? $revenueTotal / max(1, $statusCounts[Reservation::STATUS_CONFIRMED] + $statusCounts[Reservation::STATUS_COMPLETED]) : 0,
            ],
            'charts' => [
                'months' => array_column($months, 'label'),
                'revenue' => array_map(fn ($m) => round($m['revenue'], 2), array_values($months)),
                'bookings' => array_column($months, 'bookings'),
                'signups' => array_values($signups),
                'statusLabels' => array_values(Reservation::STATUSES),
                'statusValues' => array_values(array_intersect_key($statusCounts, Reservation::STATUSES)),
                'categoryLabels' => array_keys($byCategory),
                'categoryValues' => array_map(fn ($v) => round($v, 2), array_values($byCategory)),
                'roomLabels' => array_keys($byRoom),
                'roomValues' => array_values($byRoom),
            ],
        ];
    }
}
