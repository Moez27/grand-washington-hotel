<?php

namespace App\Form;

use App\Entity\Amenity;
use App\Entity\Category;
use App\Repository\RoomRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de recherche (GET) : pas de modification de données,
 * donc pas de jeton CSRF ; toutes les valeurs sont typées et validées.
 */
class RoomSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('q', SearchType::class, ['label' => 'Mot-clé', 'required' => false, 'attr' => ['placeholder' => 'Suite, vue mer...', 'maxlength' => 60], 'constraints' => [new Assert\Length(max: 60)]])
            ->add('checkIn', DateType::class, ['label' => 'Arrivée', 'widget' => 'single_text', 'input' => 'datetime_immutable', 'required' => false])
            ->add('checkOut', DateType::class, ['label' => 'Départ', 'widget' => 'single_text', 'input' => 'datetime_immutable', 'required' => false])
            ->add('guests', IntegerType::class, ['label' => 'Voyageurs', 'required' => false, 'attr' => ['min' => 1, 'max' => 12], 'constraints' => [new Assert\Range(min: 1, max: 12)]])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'label' => 'Catégorie',
                'required' => false,
                'placeholder' => 'Toutes',
                'choice_value' => 'slug',
                'choice_label' => 'name',
            ])
            ->add('minPrice', NumberType::class, ['label' => 'Prix min', 'required' => false, 'constraints' => [new Assert\PositiveOrZero()]])
            ->add('maxPrice', NumberType::class, ['label' => 'Prix max', 'required' => false, 'constraints' => [new Assert\Positive()]])
            ->add('amenities', EntityType::class, [
                'class' => Amenity::class,
                'label' => 'Équipements',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => 'name',
            ])
            ->add('sort', ChoiceType::class, [
                'label' => 'Trier par',
                'required' => false,
                'placeholder' => false,
                'choices' => array_flip(RoomRepository::SORTS),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
