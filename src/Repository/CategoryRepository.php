<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /** @return array<int, array{0: Category, roomCount: int, minPrice: ?string}> */
    public function withStats(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c, COUNT(r.id) AS roomCount, MIN(r.pricePerNight) AS minPrice')
            ->leftJoin('c.rooms', 'r', 'WITH', 'r.isActive = true')
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->getQuery()->getResult();
    }
}
