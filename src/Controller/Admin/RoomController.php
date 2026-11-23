<?php

namespace App\Controller\Admin;

use App\Entity\Room;
use App\Form\Admin\RoomType;
use App\Repository\CategoryRepository;
use App\Repository\RoomRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/chambres')]
class RoomController extends AbstractAdminController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FileUploader $uploader,
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[Route('', name: 'admin_room_index', methods: ['GET'])]
    public function index(Request $request, RoomRepository $rooms, CategoryRepository $categories, PaginatorInterface $paginator): Response
    {
        $q = mb_substr(trim((string) $request->query->get('q')), 0, 60) ?: null;
        $cat = $request->query->getInt('categorie') ?: null;

        return $this->render('admin/room/index.html.twig', [
            'rooms' => $paginator->paginate($rooms->adminQuery($q, $cat), $request->query->getInt('page', 1), 10, ['sortFieldAllowList' => ['r.name', 'r.pricePerNight', 'r.capacity']]),
            'categories' => $categories->findBy([], ['name' => 'ASC']),
            'q' => $q,
            'cat' => $cat,
        ]);
    }

    #[Route('/nouvelle', name: 'admin_room_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $room = new Room();
        $form = $this->createForm(RoomType::class, $room, ['require_image' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->save($room, $form);
            $this->addFlash('success', sprintf('La chambre « %s » a été créée.', $room->getName()));
            return $this->redirectToRoute('admin_room_show', ['id' => $room->getId()]);
        }

        return $this->render('admin/crud/form.html.twig', [
            'form' => $form,
            'title' => 'Nouvelle chambre',
            'subtitle' => 'Ajoutez une chambre au catalogue (photo obligatoire).',
            'back_path' => $this->generateUrl('admin_room_index'),
            'button_label' => 'Créer la chambre',
        ], $this->formStatus($form));
    }

    #[Route('/{id}', name: 'admin_room_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Room $room): Response
    {
        return $this->render('admin/room/show.html.twig', ['room' => $room]);
    }

    #[Route('/{id}/modifier', name: 'admin_room_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Room $room): Response
    {
        $form = $this->createForm(RoomType::class, $room, ['require_image' => null === $room->getImage()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->save($room, $form);
            $this->addFlash('success', 'Chambre mise à jour.');
            return $this->redirectToRoute('admin_room_show', ['id' => $room->getId()]);
        }

        return $this->render('admin/crud/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier « '.$room->getName().' »',
            'back_path' => $this->generateUrl('admin_room_index'),
            'current_image' => $room->getImage() ? 'uploads/rooms/'.$room->getImage() : null,
        ], $this->formStatus($form));
    }

    #[Route('/{id}/visibilite', name: 'admin_room_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Request $request, Room $room): Response
    {
        $this->denyUnlessValidToken($request, 'toggle'.$room->getId());
        $room->setIsActive(!$room->isActive());
        $this->em->flush();
        $this->addFlash('success', sprintf('« %s » est maintenant %s.', $room->getName(), $room->isActive() ? 'visible' : 'masquée'));

        return $this->redirectToRoute('admin_room_index');
    }

    #[Route('/{id}/supprimer', name: 'admin_room_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Room $room): Response
    {
        $this->denyUnlessValidToken($request, 'delete'.$room->getId());

        if ($room->getReservations()->count() > 0) {
            $this->addFlash('warning', 'Cette chambre possède des réservations : masquez-la plutôt que de la supprimer.');
            return $this->redirectToRoute('admin_room_show', ['id' => $room->getId()]);
        }

        $this->uploader->remove($room->getImage(), 'rooms');
        $this->em->remove($room);
        $this->em->flush();
        $this->addFlash('success', 'Chambre supprimée.');

        return $this->redirectToRoute('admin_room_index');
    }

    private function save(Room $room, FormInterface $form): void
    {
        $room->setSlug(strtolower($this->slugger->slug((string) $room->getName())));
        $file = $form->get('imageFile')->getData();
        if ($file) {
            $room->setImage($this->uploader->upload($file, 'rooms', $room->getImage()));
        }
        $this->em->persist($room);
        $this->em->flush();
    }
}
