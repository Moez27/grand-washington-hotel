<?php

namespace App\Controller\Admin;

use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Service\StatsProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractAdminController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function index(StatsProvider $stats, ReservationRepository $reservations, UserRepository $users): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats->dashboard(),
            'latest' => $reservations->latest(7),
            'arrivals' => $reservations->upcomingArrivals(5),
            'newClients' => $users->newestClients(5),
        ]);
    }
}
