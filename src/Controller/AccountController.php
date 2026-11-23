<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\ReservationRepository;
use App\Security\Voter\ReservationVoter;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-compte')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    #[Route('', name: 'app_account', methods: ['GET'])]
    public function index(ReservationRepository $reservations): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $all = $user->getReservations();
        $today = new \DateTimeImmutable('today');

        $upcoming = array_filter($all->toArray(), fn (Reservation $r) => $r->getCheckOut() >= $today
            && in_array($r->getStatus(), Reservation::BLOCKING_STATUSES, true));
        usort($upcoming, fn ($a, $b) => $a->getCheckIn() <=> $b->getCheckIn());

        $nights = 0;
        foreach ($all as $r) {
            if (Reservation::STATUS_COMPLETED === $r->getStatus()) {
                $nights += $r->getNights();
            }
        }

        return $this->render('account/index.html.twig', [
            'upcoming' => array_slice($upcoming, 0, 3),
            'stats' => [
                'total' => $all->count(),
                'upcoming' => count($upcoming),
                'nights' => $nights,
                'spent' => $user->getTotalSpent(),
            ],
        ]);
    }

    #[Route('/profil', name: 'app_account_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $profile = $this->createForm(ProfileType::class, $user);
        $profile->handleRequest($request);
        if ($profile->isSubmitted() && $profile->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour.');
            return $this->redirectToRoute('app_account_profile');
        }
        if ($profile->isSubmitted()) {
            $em->refresh($user); // annule les modifications invalides en mémoire
        }

        $password = $this->createForm(ChangePasswordType::class);
        $password->handleRequest($request);
        if ($password->isSubmitted() && $password->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $password->get('plainPassword')->getData()));
            $em->flush();
            $this->addFlash('success', 'Votre mot de passe a été modifié.');
            return $this->redirectToRoute('app_account_profile');
        }

        $invalid = ($profile->isSubmitted() && !$profile->isValid()) || ($password->isSubmitted() && !$password->isValid());

        return $this->render('account/profile.html.twig', [
            'profile' => $profile,
            'password' => $password,
        ], new Response(null, $invalid ? 422 : 200));
    }

    #[Route('/reservations', name: 'app_account_reservations', methods: ['GET'])]
    public function reservations(Request $request, ReservationRepository $reservations, PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $status = $request->query->get('statut');
        $status = is_string($status) && isset(Reservation::STATUSES[$status]) ? $status : null;

        return $this->render('account/reservations.html.twig', [
            'reservations' => $paginator->paginate($reservations->forUserQuery($user, $status), $request->query->getInt('page', 1), 8),
            'status' => $status,
        ]);
    }

    #[Route('/reservations/{reference}', name: 'app_account_reservation_show', methods: ['GET'], requirements: ['reference' => 'GW-[A-F0-9]{8}'])]
    public function show(#[MapEntity(mapping: ['reference' => 'reference'])] Reservation $reservation): Response
    {
        // Voter : 403 si la réservation n'appartient pas à l'utilisateur connecté
        $this->denyAccessUnlessGranted(ReservationVoter::VIEW, $reservation);

        return $this->render('account/reservation_show.html.twig', ['reservation' => $reservation]);
    }

    #[Route('/reservations/{reference}/annuler', name: 'app_account_reservation_cancel', methods: ['POST'], requirements: ['reference' => 'GW-[A-F0-9]{8}'])]
    public function cancel(#[MapEntity(mapping: ['reference' => 'reference'])] Reservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('cancel-'.$reservation->getReference(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $this->denyAccessUnlessGranted(ReservationVoter::CANCEL, $reservation);

        $reservation->setStatus(Reservation::STATUS_CANCELLED);
        $em->flush();
        $this->addFlash('info', sprintf('La réservation %s a été annulée.', $reservation->getReference()));

        return $this->redirectToRoute('app_account_reservation_show', ['reference' => $reservation->getReference()]);
    }
}
