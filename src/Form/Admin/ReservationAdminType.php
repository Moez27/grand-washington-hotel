<?php

namespace App\Form\Admin;

use App\Entity\HotelService;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'label' => 'Client',
                'placeholder' => 'Choisir un client...',
                'query_builder' => fn (UserRepository $r) => $r->createQueryBuilder('u')->orderBy('u.lastName', 'ASC'),
                'choice_label' => fn (User $u) => sprintf('%s (%s)', $u->getFullName(), $u->getEmail()),
            ])
            ->add('room', EntityType::class, [
                'class' => Room::class,
                'label' => 'Chambre',
                'placeholder' => 'Choisir une chambre...',
                'choice_label' => fn (Room $r) => sprintf('%s — %s DT/nuit', $r->getName(), number_format((float) $r->getPricePerNight(), 0, ',', ' ')),
            ])
            ->add('checkIn', DateType::class, ['label' => 'Arrivée', 'widget' => 'single_text', 'input' => 'datetime_immutable'])
            ->add('checkOut', DateType::class, ['label' => 'Départ', 'widget' => 'single_text', 'input' => 'datetime_immutable'])
            ->add('guests', IntegerType::class, ['label' => 'Voyageurs', 'attr' => ['min' => 1, 'max' => 12]])
            ->add('extras', EntityType::class, [
                'class' => HotelService::class,
                'label' => 'Services additionnels',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => 'name',
            ])
            ->add('status', ChoiceType::class, ['label' => 'Statut', 'choices' => array_flip(Reservation::STATUSES)])
            ->add('specialRequest', TextareaType::class, ['label' => 'Demande particulière', 'required' => false, 'attr' => ['rows' => 3]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Reservation::class]);
    }
}
