<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Base des contrôleurs d'administration.
 * Double protection : access_control (^/admin) + attribut IsGranted.
 */
#[IsGranted('ROLE_ADMIN')]
abstract class AbstractAdminController extends AbstractController
{
    protected function denyUnlessValidToken(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, (string) $request->getPayload()->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }

    protected function formStatus(\Symfony\Component\Form\FormInterface $form): \Symfony\Component\HttpFoundation\Response
    {
        return new \Symfony\Component\HttpFoundation\Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200);
    }
}
