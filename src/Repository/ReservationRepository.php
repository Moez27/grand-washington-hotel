<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /** Vérifie qu'aucune réservation active ne chevauche la période */
    public function isRoomAvailable(Room $room, \DateTimeImmutable $in, \DateTimeImmutable $out, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.room = :room')
            ->andWhere('r.status IN (:blocking)')
            ->andWhere('r.checkIn < :out AND r.checkOut > :in')
            ->setParameter('room', $room)
            ->setParameter('blocking', Reservation::BLOCKING_STATUSES)
            ->setParameter('in', $in, 'date_immutable')
            ->setParameter('out', $out, 'date_immutable');
        if ($excludeId) {
            $qb->andWhere('r.id != :ex')->setParameter('ex', $excludeId);
        }
        return 0 === (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** Périodes déjà réservées pour une chambre (affichées au client) @return Reservation[] */
    public function upcomingForRoom(Room $room): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.room = :room')
            ->andWhere('r.status IN (:blocking)')
            ->andWhere('r.checkOut >= :today')
            ->setParameter('room', $room)
            ->setParameter('blocking', Reservation::BLOCKING_STATUSES)
            ->setParameter('today', new \DateTimeImmutable('today'), 'date_immutable')
            ->orderBy('r.checkIn', 'ASC')
            ->setMaxResults(8)
            ->getQuery()->getResult();
    }

    public function forUserQuery(User $user, ?string $status = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->addSelect('room')
            ->join('r.room', 'room')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.checkIn', 'DESC');
        if ($status && isset(Reservation::STATUSES[$status])) {
            $qb->andWhere('r.status = :st')->setParameter('st', $status);
        }
        return $qb;
    }

    public function userHasStayed(User $user, Room $room): bool
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.user = :u AND r.room = :room')
            ->andWhere('r.status IN (:st)')
            ->setParameter('u', $user)
            ->setParameter('room', $room)
            ->setParameter('st', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_COMPLETED])
            ->getQuery()->getSingleScalarResult() > 0;
    }

    public function adminQuery(?string $q, ?string $status, ?string $from, ?string $to): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->addSelect('u', 'room')
            ->join('r.user', 'u')
            ->join('r.room', 'room')
            ->orderBy('r.createdAt', 'DESC');
        if ($q) {
            $qb->andWhere('LOWER(r.reference) LIKE :q OR LOWER(u.email) LIKE :q OR LOWER(u.lastName) LIKE :q OR LOWER(room.name) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower(addcslashes($q, '%_')).'%');
        }
        if ($status && isset(Reservation::STATUSES[$status])) {
            $qb->andWhere('r.status = :st')->setParameter('st', $status);
        }
        if ($from && ($d = \DateTimeImmutable::createFromFormat('!Y-m-d', $from))) {
            $qb->andWhere('r.checkIn >= :from')->setParameter('from', $d, 'date_immutable');
        }
        if ($to && ($d = \DateTimeImmutable::createFromFormat('!Y-m-d', $to))) {
            $qb->andWhere('r.checkIn <= :to')->setParameter('to', $d, 'date_immutable');
        }
        return $qb;
    }

    /** @return Reservation[] */
    public function latest(int $limit = 6): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('u', 'room')
            ->join('r.user', 'u')
            ->join('r.room', 'room')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /** @return Reservation[] arrivées prévues dans les 7 prochains jours */
    public function upcomingArrivals(int $limit = 6): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('u', 'room')
            ->join('r.user', 'u')
            ->join('r.room', 'room')
            ->andWhere('r.status IN (:st)')
            ->andWhere('r.checkIn BETWEEN :today AND :week')
            ->setParameter('st', Reservation::BLOCKING_STATUSES)
            ->setParameter('today', new \DateTimeImmutable('today'), 'date_immutable')
            ->setParameter('week', new \DateTimeImmutable('+7 days'), 'date_immutable')
            ->orderBy('r.checkIn', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /** @return array<string, int> */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.status AS status, COUNT(r.id) AS total')
            ->groupBy('r.status')
            ->getQuery()->getArrayResult();
        $out = array_fill_keys(array_keys(Reservation::STATUSES), 0);
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['total'];
        }
        return $out;
    }

    /**
     * Données brutes pour les statistiques (agrégées en PHP pour rester
     * compatible SQLite / MySQL / PostgreSQL).
     *
     * @return array<int, array{createdAt: \DateTimeImmutable, checkIn: \DateTimeImmutable, checkOut: \DateTimeImmutable, totalPrice: string, status: string, roomName: string, categoryName: string}>
     */
    public function statsRows(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.createdAt, r.checkIn, r.checkOut, r.totalPrice, r.status, room.name AS roomName, c.name AS categoryName')
            ->join('r.room', 'room')
            ->join('room.category', 'c')
            ->andWhere('r.checkIn >= :since')
            ->setParameter('since', $since, 'date_immutable')
            ->getQuery()->getArrayResult();
    }
}
