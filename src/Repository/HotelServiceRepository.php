<?php

namespace App\Repository;

use App\Entity\HotelService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HotelService>
 */
class HotelServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotelService::class);
    }

    /** @return HotelService[] */
    public function findActive(): array
    {
        return $this->findBy(['isActive' => true], ['price' => 'ASC']);
    }
}
