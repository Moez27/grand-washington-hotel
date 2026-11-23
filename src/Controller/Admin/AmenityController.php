<?php

namespace App\Controller\Admin;

use App\Entity\Amenity;
use App\Form\Admin\AmenityType;
use App\Repository\AmenityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/equipements')]
class AmenityController extends AbstractAdminController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'admin_amenity_index', methods: ['GET'])]
    public function index(AmenityRepository $amenities): Response
    {
        return $this->render('admin/amenity/index.html.twig', ['amenities' => $amenities->findBy([], ['name' => 'ASC'])]);
    }

    #[Route('/nouveau', name: 'admin_amenity_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/modifier', name: 'admin_amenity_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function form(Request $request, ?Amenity $amenity = null): Response
    {
        $isNew = null === $amenity;
        $amenity ??= new Amenity();
        $form = $this->createForm(AmenityType::class, $amenity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($amenity);
            $this->em->flush();
            $this->addFlash('success', $isNew ? 'Équipement ajouté.' : 'Équipement mis à jour.');
            return $this->redirectToRoute('admin_amenity_index');
        }

        return $this->render('admin/crud/form.html.twig', [
            'form' => $form,
            'title' => $isNew ? 'Nouvel équipement' : 'Modifier « '.$amenity->getName().' »',
            'back_path' => $this->generateUrl('admin_amenity_index'),
        ], $this->formStatus($form));
    }

    #[Route('/{id}/supprimer', name: 'admin_amenity_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Amenity $amenity): Response
    {
        $this->denyUnlessValidToken($request, 'delete'.$amenity->getId());
        foreach ($amenity->getRooms() as $room) {
            $room->removeAmenity($amenity);
        }
        $this->em->remove($amenity);
        $this->em->flush();
        $this->addFlash('success', 'Équipement supprimé.');

        return $this->redirectToRoute('admin_amenity_index');
    }
}
