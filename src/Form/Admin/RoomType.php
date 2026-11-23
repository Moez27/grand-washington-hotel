<?php

namespace App\Form\Admin;

use App\Entity\Amenity;
use App\Entity\Category;
use App\Entity\Room;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom de la chambre'])
            ->add('category', EntityType::class, ['class' => Category::class, 'label' => 'Catégorie', 'placeholder' => 'Choisir...', 'choice_label' => 'name'])
            ->add('shortDescription', TextType::class, ['label' => 'Accroche', 'help' => 'Une phrase affichée sur les cartes (255 caractères max).'])
            ->add('description', TextareaType::class, ['label' => 'Description complète', 'attr' => ['rows' => 6]])
            ->add('pricePerNight', MoneyType::class, ['label' => 'Prix / nuit', 'currency' => 'TND', 'scale' => 2])
            ->add('capacity', IntegerType::class, ['label' => 'Capacité (personnes)', 'attr' => ['min' => 1, 'max' => 12]])
            ->add('size', IntegerType::class, ['label' => 'Surface (m²)', 'attr' => ['min' => 8]])
            ->add('bedType', TextType::class, ['label' => 'Literie', 'attr' => ['placeholder' => '1 lit King size']])
            ->add('amenities', EntityType::class, [
                'class' => Amenity::class,
                'label' => 'Équipements',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => 'name',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Photo principale',
                'mapped' => false,
                'required' => $options['require_image'],
                'help' => 'JPG, PNG ou WEBP — 3 Mo maximum.',
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp', 'data-preview' => 'room-preview'],
                'constraints' => array_filter([
                    $options['require_image'] ? new Assert\NotNull(message: 'Une photo est obligatoire.') : null,
                    new Assert\Image(
                        maxSize: '3M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Formats acceptés : JPG, PNG, WEBP.',
                        maxWidth: 6000,
                        maxHeight: 6000,
                    ),
                ]),
            ])
            ->add('isActive', CheckboxType::class, ['label' => 'Visible sur le site', 'required' => false])
            ->add('isFeatured', CheckboxType::class, ['label' => 'Mise en avant (page d\'accueil)', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Room::class, 'require_image' => false]);
        $resolver->setAllowedTypes('require_image', 'bool');
    }
}
