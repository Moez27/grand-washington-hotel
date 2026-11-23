<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Liste blanche des champs de tri acceptés dans l'URL (?_sort=...).
 * Toute autre valeur est ignorée : impossible de trier (et donc de deviner)
 * sur un champ sensible comme le mot de passe.
 */
class SortParameterSubscriber implements EventSubscriberInterface
{
    private const ALLOWED = [
        'r.name', 'r.pricePerNight', 'r.capacity', 'r.checkIn', 'r.totalPrice',
        'u.lastName', 'u.createdAt',
    ];

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 20]];
    }

    public function onRequest(RequestEvent $event): void
    {
        $query = $event->getRequest()->query;
        if ($query->has('_sort') && !in_array($query->get('_sort'), self::ALLOWED, true)) {
            $query->remove('_sort');
            $query->remove('_dir');
        }
        if ($query->has('_dir') && !in_array(strtolower((string) $query->get('_dir')), ['asc', 'desc'], true)) {
            $query->remove('_dir');
        }
    }
}
