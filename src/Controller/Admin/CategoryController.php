<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\Admin\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/categories')]
class CategoryController extends AbstractAdminController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly SluggerInterface $slugger)
    {
    }

    #[Route('', name: 'admin_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categories): Response
    {
        return $this->render('admin/category/index.html.twig', ['categories' => $categories->withStats()]);
    }

    #[Route('/nouvelle', name: 'admin_category_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/modifier', name: 'admin_category_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function form(Request $request, ?Category $category = null): Response
    {
        $isNew = null === $category;
        $category ??= new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setSlug(strtolower($this->slugger->slug((string) $category->getName())));
            $this->em->persist($category);
            $this->em->flush();
            $this->addFlash('success', $isNew ? 'Catégorie créée.' : 'Catégorie mise à jour.');
            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/crud/form.html.twig', [
            'form' => $form,
            'title' => $isNew ? 'Nouvelle catégorie' : 'Modifier « '.$category->getName().' »',
            'back_path' => $this->generateUrl('admin_category_index'),
        ], $this->formStatus($form));
    }

    #[Route('/{id}/supprimer', name: 'admin_category_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Category $category): Response
    {
        $this->denyUnlessValidToken($request, 'delete'.$category->getId());
        if ($category->getRooms()->count() > 0) {
            $this->addFlash('warning', 'Impossible : des chambres sont rattachées à cette catégorie.');
        } else {
            $this->em->remove($category);
            $this->em->flush();
            $this->addFlash('success', 'Catégorie supprimée.');
        }

        return $this->redirectToRoute('admin_category_index');
    }
}
