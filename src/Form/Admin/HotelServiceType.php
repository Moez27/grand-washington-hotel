<?php

namespace App\Form\Admin;

use App\Entity\HotelService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class HotelServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom du service'])
            ->add('description', TextareaType::class, ['label' => 'Description', 'attr' => ['rows' => 4]])
            ->add('price', MoneyType::class, ['label' => 'Prix', 'currency' => 'TND', 'scale' => 2])
            ->add('pricingType', ChoiceType::class, ['label' => 'Tarification', 'choices' => array_flip(HotelService::PRICING_LABELS)])
            ->add('icon', TextType::class, ['label' => 'Icône Bootstrap', 'help' => 'Ex. bi-cup-hot, bi-water, bi-car-front', 'attr' => ['data-icon-input' => '']])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'help' => 'JPG, PNG ou WEBP — 3 Mo maximum.',
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp', 'data-preview' => 'service-preview'],
                'constraints' => [new Assert\Image(maxSize: '3M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'], mimeTypesMessage: 'Formats acceptés : JPG, PNG, WEBP.')],
            ])
            ->add('isActive', CheckboxType::class, ['label' => 'Proposé aux clients', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => HotelService::class]);
    }
}
