<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Form\Admin\ReservationAdminType;
use App\Repository\ReservationRepository;
use App\Service\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/reservations')]
class ReservationController extends AbstractAdminController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ReservationRepository $reservations,
        private readonly Cart $pricing,
    ) {
    }

    #[Route('', name: 'admin_reservation_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $this->filters($request);

        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $paginator->paginate(
                $this->reservations->adminQuery(...array_values($filters)),
                $request->query->getInt('page', 1),
                15,
                ['sortFieldAllowList' => ['r.checkIn', 'r.totalPrice']],
            ),
            'filters' => $filters,
            'counts' => $this->reservations->countByStatus(),
        ]);
    }

    #[Route('/export', name: 'admin_reservation_export', methods: ['GET'])]
    public function export(Request $request): StreamedResponse
    {
        $rows = $this->reservations->adminQuery(...array_values($this->filters($request)))->getQuery()->toIterable();

        $response = new StreamedResponse(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($out, ['Référence', 'Client', 'E-mail', 'Chambre', 'Arrivée', 'Départ', 'Nuits', 'Voyageurs', 'Montant (DT)', 'Statut', 'Créée le'], ';');
            foreach ($rows as $r) {
                /** @var Reservation $r */
                fputcsv($out, array_map([$this, 'csvSafe'], [
                    $r->getReference(), $r->getUser()->getFullName(), $r->getUser()->getEmail(), $r->getRoom()->getName(),
                    $r->getCheckIn()->format('d/m/Y'), $r->getCheckOut()->format('d/m/Y'), $r->getNights(), $r->getGuests(),
                    number_format($r->getTotalPrice(), 2, ',', ''), $r->getStatusLabel(), $r->getCreatedAt()->format('d/m/Y H:i'),
                ]), ';');
            }
            fclose($out);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="reservations-'.date('Y-m-d').'.csv"');

        return $response;
    }

    #[Route('/nouvelle', name: 'admin_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $reservation = (new Reservation())
            ->setCheckIn(new \DateTimeImmutable('tomorrow'))
            ->setCheckOut(new \DateTimeImmutable('+3 days midnight'))
            ->setStatus(Reservation::STATUS_CONFIRMED);

        return $this->handleForm($request, $reservation, 'Nouvelle réservation');
    }

    #[Route('/{id}', name: 'admin_reservation_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('admin/reservation/show.html.twig', ['reservation' => $reservation]);
    }

    #[Route('/{id}/modifier', name: 'admin_reservation_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Reservation $reservation): Response
    {
        return $this->handleForm($request, $reservation, 'Modifier '.$reservation->getReference());
    }

    #[Route('/{id}/statut', name: 'admin_reservation_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function status(Request $request, Reservation $reservation): Response
    {
        $this->denyUnlessValidToken($request, 'status'.$reservation->getId());
        $status = (string) $request->getPayload()->get('status');

        if (!isset(Reservation::STATUSES[$status])) {
            $this->addFlash('danger', 'Statut invalide.');
        } elseif (in_array($status, Reservation::BLOCKING_STATUSES, true)
            && !$this->reservations->isRoomAvailable($reservation->getRoom(), $reservation->getCheckIn(), $reservation->getCheckOut(), $reservation->getId())) {
            $this->addFlash('danger', 'Impossible : la chambre est déjà réservée sur cette période.');
        } else {
            $reservation->setStatus($status);
            $this->em->flush();
            $this->addFlash('success', sprintf('%s → %s', $reservation->getReference(), $reservation->getStatusLabel()));
        }

        return $this->redirectToRoute($request->getPayload()->get('from') === 'show' ? 'admin_reservation_show' : 'admin_reservation_index', $request->getPayload()->get('from') === 'show' ? ['id' => $reservation->getId()] : []);
    }

    #[Route('/{id}/supprimer', name: 'admin_reservation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Reservation $reservation): Response
    {
        $this->denyUnlessValidToken($request, 'delete'.$reservation->getId());
        $this->em->remove($reservation);
        $this->em->flush();
        $this->addFlash('success', 'Réservation supprimée.');

        return $this->redirectToRoute('admin_reservation_index');
    }

    private function handleForm(Request $request, Reservation $reservation, string $title): Response
    {
        $form = $this->createForm(ReservationAdminType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->checkBusinessRules($form, $reservation)) {
            // Le montant est toujours recalculé côté serveur
            $quote = $this->pricing->quote($reservation->getRoom(), $reservation->getCheckIn(), $reservation->getCheckOut(), $reservation->getGuests(), $reservation->getExtras());
            $reservation->setTotalPrice($quote['total']);
            $this->em->persist($reservation);
            $this->em->flush();
            $this->addFlash('success', sprintf('Réservation %s enregistrée (%s DT).', $reservation->getReference(), number_format($quote['total'], 0, ',', ' ')));

            return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
        }

        return $this->render('admin/crud/form.html.twig', [
            'form' => $form,
            'title' => $title,
            'subtitle' => 'Le montant total est recalculé automatiquement (chambre + services).',
            'back_path' => $this->generateUrl('admin_reservation_index'),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }

    private function checkBusinessRules(FormInterface $form, Reservation $r): bool
    {
        $ok = true;
        if ($r->getGuests() > $r->getRoom()->getCapacity()) {
            $form->get('guests')->addError(new FormError(sprintf('Capacité maximale de la chambre : %d.', $r->getRoom()->getCapacity())));
            $ok = false;
        }
        if (in_array($r->getStatus(), Reservation::BLOCKING_STATUSES, true)
            && !$this->reservations->isRoomAvailable($r->getRoom(), $r->getCheckIn(), $r->getCheckOut(), $r->getId())) {
            $form->get('checkIn')->addError(new FormError('La chambre est déjà réservée sur cette période.'));
            $ok = false;
        }
        return $ok;
    }

    /** @return array{q: ?string, status: ?string, from: ?string, to: ?string} */
    private function filters(Request $request): array
    {
        $str = fn (string $k) => ($v = trim((string) $request->query->get($k))) !== '' ? mb_substr($v, 0, 60) : null;
        return ['q' => $str('q'), 'status' => $str('status'), 'from' => $str('from'), 'to' => $str('to')];
    }

    /** Neutralise l'injection de formules dans Excel (CSV injection) */
    public function csvSafe(mixed $v): string
    {
        $v = (string) $v;
        return preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;
    }
}
