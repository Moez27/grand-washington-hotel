<?php

namespace App\Form;

use App\Entity\HotelService;
use App\Model\BookingData;
use App\Repository\HotelServiceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $builder
            ->add('checkIn', DateType::class, [
                'label' => 'Arrivée',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['min' => $today, 'data-checkin' => ''],
            ])
            ->add('checkOut', DateType::class, [
                'label' => 'Départ',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['min' => $today, 'data-checkout' => ''],
            ])
            ->add('guests', IntegerType::class, [
                'label' => 'Voyageurs',
                'attr' => ['min' => 1, 'max' => $options['max_guests'], 'data-guests' => ''],
            ])
            ->add('extras', EntityType::class, [
                'class' => HotelService::class,
                'label' => 'Services additionnels',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'query_builder' => fn (HotelServiceRepository $r) => $r->createQueryBuilder('s')
                    ->andWhere('s.isActive = true')->orderBy('s.price', 'ASC'),
                'choice_label' => fn (HotelService $s) => sprintf('%s — %s DT %s', $s->getName(), number_format((float) $s->getPrice(), 0, ',', ' '), $s->getPricingLabel()),
                'choice_attr' => fn (HotelService $s) => [
                    'data-price' => $s->getPrice(),
                    'data-pricing' => $s->getPricingType(),
                ],
            ])
            ->add('specialRequest', TextareaType::class, [
                'label' => 'Demande particulière',
                'required' => false,
                'attr' => ['rows' => 2, 'maxlength' => 500, 'placeholder' => 'Lit bébé, arrivée tardive...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BookingData::class,
            'max_guests' => 12,
        ]);
    }
}
