<?php

namespace App\Controller;

use App\Form\RoomSearchType;
use App\Repository\CategoryRepository;
use App\Repository\HotelServiceRepository;
use App\Repository\ReviewRepository;
use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        RoomRepository $rooms,
        CategoryRepository $categories,
        HotelServiceRepository $services,
        ReviewRepository $reviews,
    ): Response {
        $search = $this->createForm(RoomSearchType::class, null, ['action' => $this->generateUrl('app_room_index')]);

        return $this->render('home/index.html.twig', [
            'search' => $search,
            'featured' => $rooms->findFeatured(6),
            'categories' => $categories->withStats(),
            'services' => array_slice($services->findActive(), 0, 3),
            'testimonials' => $reviews->latestApproved(3),
            'average' => $reviews->globalAverage(),
            'roomCount' => $rooms->count(['isActive' => true]),
        ]);
    }

    #[Route('/services', name: 'app_services', methods: ['GET'])]
    public function services(HotelServiceRepository $services): Response
    {
        return $this->render('home/services.html.twig', [
            'services' => $services->findActive(),
        ]);
    }
}
