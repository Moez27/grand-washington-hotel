<?php

namespace App\Form\Admin;

use App\Entity\User;
use App\Form\PasswordFields;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = $options['is_new'];
        $password = PasswordFields::options($isNew);
        if (!$isNew) {
            $password['first_options']['help'] = 'Laisser vide pour conserver le mot de passe actuel.';
            $password['first_options']['label'] = 'Nouveau mot de passe';
        }

        $builder
            ->add('firstName', TextType::class, ['label' => 'Prénom'])
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            ->add('email', EmailType::class, ['label' => 'E-mail'])
            ->add('phone', TelType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => ['Client' => 'ROLE_USER', 'Administrateur' => 'ROLE_ADMIN'],
                'multiple' => true,
                'expanded' => true,
                'disabled' => $options['is_self'],
                'help' => $options['is_self'] ? 'Vous ne pouvez pas modifier votre propre rôle.' : null,
            ])
            ->add('isBlocked', CheckboxType::class, [
                'label' => 'Compte suspendu (connexion impossible)',
                'required' => false,
                'disabled' => $options['is_self'],
            ])
            ->add('plainPassword', RepeatedType::class, $password);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class, 'is_new' => false, 'is_self' => false]);
    }
}
