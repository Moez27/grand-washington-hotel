<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\UserAdminType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/clients')]
class UserController extends AbstractAdminController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly UserPasswordHasherInterface $hasher)
    {
    }

    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $users, PaginatorInterface $paginator): Response
    {
        $q = mb_substr(trim((string) $request->query->get('q')), 0, 60) ?: null;
        $role = in_array($request->query->get('role'), ['admin', 'client'], true) ? $request->query->get('role') : null;
        $state = in_array($request->query->get('etat'), ['active', 'blocked'], true) ? $request->query->get('etat') : null;

        return $this->render('admin/user/index.html.twig', [
            'users' => $paginator->paginate($users->adminQuery($q, $role, $state), $request->query->getInt('page', 1), 15, ['sortFieldAllowList' => ['u.lastName', 'u.createdAt']]),
            'q' => $q, 'role' => $role, 'state' => $state,
        ]);
    }

    #[Route('/nouveau', name: 'admin_user_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/modifier', name: 'admin_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function form(Request $request, ?User $user = null): Response
    {
        $isNew = null === $user;
        $user ??= new User();
        $isSelf = !$isNew && $user === $this->getUser();

        $form = $this->createForm(UserAdminType::class, $user, ['is_new' => $isNew, 'is_self' => $isSelf]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($plain = $form->get('plainPassword')->getData()) {
                $user->setPassword($this->hasher->hashPassword($user, $plain));
            }
            // Seuls les rôles connus sont acceptés
            $user->setRoles(array_values(array_intersect($user->getRoles(), ['ROLE_ADMIN'])));
            $this->em->persist($user);
            $this->em->flush();
            $this->addFlash('success', $isNew ? 'Utilisateur créé.' : 'Utilisateur mis à jour.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/crud/form.html.twig', [
            'form' => $form,
            'title' => $isNew ? 'Nouvel utilisateur' : 'Modifier '.$user->getFullName(),
            'back_path' => $this->generateUrl('admin_user_index'),
        ], $this->formStatus($form));
    }

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', ['user' => $user]);
    }

    #[Route('/{id}/bloquer', name: 'admin_user_toggle_block', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleBlock(Request $request, User $user): Response
    {
        $this->denyUnlessValidToken($request, 'block'.$user->getId());
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas suspendre votre propre compte.');
        } else {
            $user->setIsBlocked(!$user->isBlocked());
            $this->em->flush();
            $this->addFlash('success', sprintf('Compte de %s %s.', $user->getFullName(), $user->isBlocked() ? 'suspendu' : 'réactivé'));
        }

        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'admin_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, User $user): Response
    {
        $this->denyUnlessValidToken($request, 'delete'.$user->getId());
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_user_index');
        }
        $this->em->remove($user); // ses réservations et avis sont supprimés (orphanRemoval)
        $this->em->flush();
        $this->addFlash('success', 'Utilisateur supprimé.');

        return $this->redirectToRoute('admin_user_index');
    }
}
