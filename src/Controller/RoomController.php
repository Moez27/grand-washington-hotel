<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Room;
use App\Entity\User;
use App\Form\BookingType;
use App\Form\ReviewType;
use App\Form\RoomSearchType;
use App\Model\BookingData;
use App\Repository\ReservationRepository;
use App\Repository\ReviewRepository;
use App\Repository\RoomRepository;
use App\Service\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chambres')]
class RoomController extends AbstractController
{
    #[Route('', name: 'app_room_index', methods: ['GET'])]
    public function index(Request $request, RoomRepository $rooms, PaginatorInterface $paginator): Response
    {
        $form = $this->createForm(RoomSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $criteria = [
                'q' => $d['q'] ?? null,
                'category' => $d['category']?->getSlug(),
                'minPrice' => $d['minPrice'] ?? null,
                'maxPrice' => $d['maxPrice'] ?? null,
                'guests' => $d['guests'] ?? null,
                'amenities' => array_map(fn ($a) => $a->getId(), iterator_to_array($d['amenities'] ?? [])),
                'sort' => $d['sort'] ?? null,
            ];
            $in = $d['checkIn'] ?? null;
            $out = $d['checkOut'] ?? null;
            if ($in && $out && $out > $in && $in >= new \DateTimeImmutable('today')) {
                $criteria['checkIn'] = $in;
                $criteria['checkOut'] = $out;
            } elseif ($in || $out) {
                $this->addFlash('warning', 'Les dates saisies sont invalides : la disponibilité n\'a pas été filtrée.');
            }
        }

        $pagination = $paginator->paginate(
            $rooms->searchQuery($criteria),
            $request->query->getInt('page', 1),
            9,
            ['distinct' => true, 'wrap-queries' => true],
        );

        return $this->render('room/index.html.twig', [
            'form' => $form,
            'rooms' => $pagination,
            'filtered' => array_filter($criteria, fn ($v) => !empty($v)),
            'datesApplied' => isset($criteria['checkIn']),
            'priceRange' => $rooms->priceRange(),
        ]);
    }

    #[Route('/{slug}', name: 'app_room_show', methods: ['GET', 'POST'], requirements: ['slug' => '[a-z0-9\-]+'])]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Room $room,
        Request $request,
        Cart $cart,
        ReservationRepository $reservations,
        ReviewRepository $reviews,
        RoomRepository $rooms,
    ): Response {
        if (!$room->isActive() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $data = new BookingData();
        // Pré-remplissage depuis la recherche (?checkIn=...&checkOut=...&guests=...)
        $data->checkIn = $this->parseDate($request->query->get('checkIn')) ?? new \DateTimeImmutable('tomorrow');
        $data->checkOut = $this->parseDate($request->query->get('checkOut')) ?? $data->checkIn->modify('+2 days');
        $data->guests = min($room->getCapacity(), max(1, $request->query->getInt('guests', min(2, $room->getCapacity()))));

        $booking = $this->createForm(BookingType::class, $data, ['max_guests' => $room->getCapacity()]);
        $booking->handleRequest($request);

        if ($booking->isSubmitted() && $booking->isValid()) {
            $error = $cart->add($room, $data->checkIn, $data->checkOut, (int) $data->guests, $data->extras, $data->specialRequest);
            if (null === $error) {
                $this->addFlash('success', sprintf('« %s » a été ajoutée à votre panier.', $room->getName()));
                return $this->redirectToRoute('app_cart');
            }
            $this->addFlash('danger', $error);
        }

        // Avis : seul un client ayant séjourné (réservation confirmée/terminée) peut noter
        $reviewForm = null;
        $user = $this->getUser();
        if ($user instanceof User
            && $reservations->userHasStayed($user, $room)
            && !$reviews->findOneByUserAndRoom($user, $room)) {
            $reviewForm = $this->createForm(ReviewType::class, new Review(), [
                'action' => $this->generateUrl('app_room_review', ['slug' => $room->getSlug()]),
            ]);
        }

        $similar = array_filter(
            $rooms->findBy(['category' => $room->getCategory(), 'isActive' => true], ['pricePerNight' => 'ASC'], 4),
            fn (Room $r) => $r->getId() !== $room->getId()
        );

        return $this->render('room/show.html.twig', [
            'room' => $room,
            'booking' => $booking,
            'reviewForm' => $reviewForm,
            'booked' => $reservations->upcomingForRoom($room),
            'similar' => array_slice($similar, 0, 3),
        ], new Response(null, $booking->isSubmitted() && !$booking->isValid() ? 422 : 200));
    }

    #[Route('/{slug}/avis', name: 'app_room_review', methods: ['POST'], requirements: ['slug' => '[a-z0-9\-]+'])]
    #[IsGranted('ROLE_USER')]
    public function review(
        #[MapEntity(mapping: ['slug' => 'slug'])] Room $room,
        Request $request,
        ReservationRepository $reservations,
        ReviewRepository $reviews,
        EntityManagerInterface $em,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$reservations->userHasStayed($user, $room) || $reviews->findOneByUserAndRoom($user, $room)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas noter cette chambre.');
        }

        $review = (new Review())->setUser($user)->setRoom($room);
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($review);
            $em->flush();
            $this->addFlash('success', 'Merci ! Votre avis sera publié après modération.');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->redirectToRoute('app_room_show', ['slug' => $room->getSlug(), '_fragment' => 'avis']);
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $d && $d >= new \DateTimeImmutable('today') ? $d : null;
    }
}
