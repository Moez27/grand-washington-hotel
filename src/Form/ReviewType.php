<?php

namespace App\Form;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', ChoiceType::class, [
                'label' => 'Votre note',
                'choices' => ['★★★★★ Excellent' => 5, '★★★★ Très bien' => 4, '★★★ Bien' => 3, '★★ Moyen' => 2, '★ Décevant' => 1],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Votre avis',
                'attr' => ['rows' => 3, 'maxlength' => 1000, 'placeholder' => 'Racontez-nous votre séjour...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Review::class]);
    }
}
