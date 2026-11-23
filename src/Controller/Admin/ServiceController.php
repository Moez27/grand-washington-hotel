<?php

namespace App\Controller\Admin;

use App\Entity\HotelService;
use App\Entity\Reservation;
use App\Form\Admin\HotelServiceType;
use App\Repository\HotelServiceRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/services')]
class ServiceController extends AbstractAdminController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly FileUploader $uploader)
    {
    }

    #[Route('', name: 'admin_service_index', methods: ['GET'])]
    public function index(HotelServiceRepository $services): Response
    {
        return $this->render('admin/service/index.html.twig', ['services' => $services->findBy([], ['name' => 'ASC'])]);
    }

    #[Route('/nouveau', name: 'admin_service_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/modifier', name: 'admin_service_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function form(Request $request, ?HotelService $service = null): Response
    {
        $isNew = null === $service;
        $service ??= new HotelService();
        $form = $this->createForm(HotelServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($file = $form->get('imageFile')->getData()) {
                $service->setImage($this->uploader->upload($file, 'services', $service->getImage()));
            }
            $this->em->persist($service);
            $this->em->flush();
            $this->addFlash('success', $isNew ? 'Service créé.' : 'Service mis à jour.');
            return $this->redirectToRoute('admin_service_index');
        }

        return $this->render('admin/crud/form.html.twig', [
            'form' => $form,
            'title' => $isNew ? 'Nouveau service' : 'Modifier « '.$service->getName().' »',
            'back_path' => $this->generateUrl('admin_service_index'),
            'current_image' => $service->getImage() ? 'uploads/services/'.$service->getImage() : null,
        ], $this->formStatus($form));
    }

    #[Route('/{id}/supprimer', name: 'admin_service_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, HotelService $service): Response
    {
        $this->denyUnlessValidToken($request, 'delete'.$service->getId());

        $used = (int) $this->em->createQueryBuilder()
            ->select('COUNT(r.id)')->from(Reservation::class, 'r')
            ->andWhere(':s MEMBER OF r.extras')->setParameter('s', $service)
            ->getQuery()->getSingleScalarResult();

        if ($used > 0) {
            $service->setIsActive(false);
            $this->em->flush();
            $this->addFlash('warning', sprintf('Ce service figure dans %d réservation(s) : il a été désactivé au lieu d\'être supprimé.', $used));
        } else {
            $this->uploader->remove($service->getImage(), 'services');
            $this->em->remove($service);
            $this->em->flush();
            $this->addFlash('success', 'Service supprimé.');
        }

        return $this->redirectToRoute('admin_service_index');
    }
}
