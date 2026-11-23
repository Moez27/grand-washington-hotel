<?php

namespace App\Repository;

use App\Entity\Review;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /** @return Review[] */
    public function latestApproved(int $limit = 3): array
    {
        return $this->createQueryBuilder('rv')
            ->addSelect('u', 'room')
            ->join('rv.user', 'u')
            ->join('rv.room', 'room')
            ->andWhere('rv.isApproved = true')
            ->andWhere('rv.rating >= 4')
            ->orderBy('rv.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    public function findOneByUserAndRoom(User $user, Room $room): ?Review
    {
        return $this->findOneBy(['user' => $user, 'room' => $room]);
    }

    public function adminQuery(?string $state): QueryBuilder
    {
        $qb = $this->createQueryBuilder('rv')
            ->addSelect('u', 'room')
            ->join('rv.user', 'u')
            ->join('rv.room', 'room')
            ->orderBy('rv.isApproved', 'ASC')
            ->addOrderBy('rv.createdAt', 'DESC');
        if ('pending' === $state) {
            $qb->andWhere('rv.isApproved = false');
        } elseif ('approved' === $state) {
            $qb->andWhere('rv.isApproved = true');
        }
        return $qb;
    }

    public function countPending(): int
    {
        return $this->count(['isApproved' => false]);
    }

    public function globalAverage(): ?float
    {
        $avg = $this->createQueryBuilder('rv')
            ->select('AVG(rv.rating)')
            ->andWhere('rv.isApproved = true')
            ->getQuery()->getSingleScalarResult();
        return $avg !== null ? round((float) $avg, 1) : null;
    }
}
