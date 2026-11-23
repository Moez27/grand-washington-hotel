<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Options communes au champ "nouveau mot de passe" (inscription, changement,
 * création d'utilisateur par l'admin) : politique de mot de passe robuste.
 */
final class PasswordFields
{
    public static function options(bool $required = true): array
    {
        $constraints = [
            new Assert\Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
            new Assert\Regex(
                pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                message: 'Le mot de passe doit contenir une majuscule, une minuscule et un chiffre.'
            ),
        ];
        if ($required) {
            array_unshift($constraints, new Assert\NotBlank(message: 'Choisissez un mot de passe.'));
        }

        return [
            'type' => PasswordType::class,
            'mapped' => false,
            'required' => $required,
            'invalid_message' => 'Les deux mots de passe ne correspondent pas.',
            'first_options' => [
                'label' => 'Mot de passe',
                'attr' => ['autocomplete' => 'new-password'],
                'help' => '8 caractères minimum, avec majuscule, minuscule et chiffre.',
            ],
            'second_options' => ['label' => 'Confirmer le mot de passe', 'attr' => ['autocomplete' => 'new-password']],
            'constraints' => $constraints,
        ];
    }
}
