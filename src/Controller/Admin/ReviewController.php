<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/avis')]
class ReviewController extends AbstractAdminController
{
    #[Route('', name: 'admin_review_index', methods: ['GET'])]
    public function index(Request $request, ReviewRepository $reviews, PaginatorInterface $paginator): Response
    {
        $state = in_array($request->query->get('etat'), ['pending', 'approved'], true) ? $request->query->get('etat') : null;

        return $this->render('admin/review/index.html.twig', [
            'reviews' => $paginator->paginate($reviews->adminQuery($state), $request->query->getInt('page', 1), 12),
            'state' => $state,
            'pendingCount' => $reviews->countPending(),
        ]);
    }

    #[Route('/{id}/moderer', name: 'admin_review_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Request $request, Review $review, EntityManagerInterface $em): Response
    {
        $this->denyUnlessValidToken($request, 'review'.$review->getId());
        $review->setIsApproved(!$review->isApproved());
        $em->flush();
        $this->addFlash('success', $review->isApproved() ? 'Avis publié.' : 'Avis masqué.');

        return $this->redirectToRoute('admin_review_index', array_filter(['etat' => $request->query->get('etat')]));
    }

    #[Route('/{id}/supprimer', name: 'admin_review_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Review $review, EntityManagerInterface $em): Response
    {
        $this->denyUnlessValidToken($request, 'delete'.$review->getId());
        $em->remove($review);
        $em->flush();
        $this->addFlash('success', 'Avis supprimé.');

        return $this->redirectToRoute('admin_review_index');
    }
}
