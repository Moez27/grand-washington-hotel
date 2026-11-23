<?php

namespace App\Security\Voter;

use App\Entity\Reservation;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Contrôle d'accès objet : un client ne peut voir / annuler QUE ses propres
 * réservations (protection contre les IDOR : /mon-compte/reservations/42).
 *
 * @extends Voter<string, Reservation>
 */
class ReservationVoter extends Voter
{
    public const VIEW = 'RESERVATION_VIEW';
    public const CANCEL = 'RESERVATION_CANCEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::CANCEL], true) && $subject instanceof Reservation;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }
        /** @var Reservation $subject */
        $isOwner = $subject->getUser()?->getId() === $user->getId();

        return match ($attribute) {
            self::VIEW => $isOwner,
            self::CANCEL => $isOwner && $subject->isCancellable(),
            default => false,
        };
    }
}
