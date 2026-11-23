<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Service\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panier')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart', methods: ['GET'])]
    public function index(Cart $cart): Response
    {
        $lines = $cart->lines();

        return $this->render('cart/index.html.twig', [
            'lines' => $lines,
            'total' => $cart->total($lines),
        ]);
    }

    #[Route('/retirer/{key}', name: 'app_cart_remove', methods: ['POST'], requirements: ['key' => '[a-f0-9]{12}'])]
    #[IsCsrfTokenValid('cart-remove', tokenKey: '_token')]
    public function remove(string $key, Cart $cart): Response
    {
        $cart->remove($key);
        $this->addFlash('info', 'Le séjour a été retiré de votre panier.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/vider', name: 'app_cart_clear', methods: ['POST'])]
    #[IsCsrfTokenValid('cart-clear', tokenKey: '_token')]
    public function clear(Cart $cart): Response
    {
        $cart->clear();

        return $this->redirectToRoute('app_cart');
    }

    /**
     * Transforme le panier en réservations. Les prix et la disponibilité sont
     * re-vérifiés côté serveur, dans une transaction.
     */
    #[Route('/valider', name: 'app_cart_checkout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    #[IsCsrfTokenValid('cart-checkout', tokenKey: '_token')]
    public function checkout(Cart $cart, ReservationRepository $reservations, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $lines = $cart->lines();
        if (!$lines) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_room_index');
        }

        foreach ($lines as $line) {
            if (!$line['available']) {
                $this->addFlash('danger', sprintf('« %s » n\'est plus disponible sur ces dates : retirez-la du panier.', $line['room']->getName()));
                return $this->redirectToRoute('app_cart');
            }
        }

        $created = [];
        try {
            $this->persistReservations($lines, $user, $reservations, $em, $created);
        } catch (\RuntimeException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('app_cart');
        }

        $cart->clear();
        $this->addFlash('success', sprintf(
            '%d réservation(s) enregistrée(s) ! Notre équipe va confirmer votre séjour très rapidement.',
            count($created)
        ));

        return $this->redirectToRoute('app_account_reservations');
    }

    private function persistReservations(array $lines, User $user, ReservationRepository $reservations, EntityManagerInterface $em, array &$created): void
    {
        $em->wrapInTransaction(function () use ($lines, $user, $reservations, $em, &$created) {
            foreach ($lines as $line) {
                if (!$reservations->isRoomAvailable($line['room'], $line['checkIn'], $line['checkOut'])) {
                    throw new \RuntimeException(sprintf('« %s » n\'est plus disponible du %s au %s.', $line['room']->getName(), $line['checkIn']->format('d/m/Y'), $line['checkOut']->format('d/m/Y')));
                }
                $res = (new Reservation())
                    ->setUser($user)
                    ->setRoom($line['room'])
                    ->setCheckIn($line['checkIn'])
                    ->setCheckOut($line['checkOut'])
                    ->setGuests($line['guests'])
                    ->setSpecialRequest($line['request'])
                    ->setTotalPrice($line['quote']['total'])
                    ->setStatus(Reservation::STATUS_PENDING);
                foreach ($line['extrasEntities'] as $extra) {
                    $res->addExtra($extra);
                }
                $em->persist($res);
                $created[] = $res;
            }
            $em->flush();
        });
    }
}
