<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Toutes les requêtes utilisent le QueryBuilder avec des paramètres liés
 * (setParameter) : aucune valeur utilisateur n'est concaténée dans le DQL,
 * ce qui protège contre l'injection SQL.
 *
 * @extends ServiceEntityRepository<Room>
 */
class RoomRepository extends ServiceEntityRepository
{
    public const SORTS = [
        'recommended' => 'Recommandées',
        'price_asc' => 'Prix croissant',
        'price_desc' => 'Prix décroissant',
        'capacity' => 'Capacité',
        'newest' => 'Nouveautés',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    /** @return Room[] */
    public function findFeatured(int $limit = 6): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('c')
            ->join('r.category', 'c')
            ->andWhere('r.isActive = true')
            ->orderBy('r.isFeatured', 'DESC')
            ->addOrderBy('r.pricePerNight', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /**
     * Recherche multicritère côté client.
     *
     * @param array{category?: ?string, minPrice?: ?float, maxPrice?: ?float, guests?: ?int,
     *              checkIn?: ?\DateTimeImmutable, checkOut?: ?\DateTimeImmutable,
     *              amenities?: int[], q?: ?string, sort?: ?string} $c
     */
    public function searchQuery(array $c): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->addSelect('c')
            ->join('r.category', 'c')
            ->andWhere('r.isActive = true');

        if (!empty($c['q'])) {
            $qb->andWhere('LOWER(r.name) LIKE :q OR LOWER(r.shortDescription) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower(addcslashes($c['q'], '%_')).'%');
        }
        if (!empty($c['category'])) {
            $qb->andWhere('c.slug = :cat')->setParameter('cat', $c['category']);
        }
        if (!empty($c['minPrice'])) {
            $qb->andWhere('r.pricePerNight >= :min')->setParameter('min', $c['minPrice']);
        }
        if (!empty($c['maxPrice'])) {
            $qb->andWhere('r.pricePerNight <= :max')->setParameter('max', $c['maxPrice']);
        }
        if (!empty($c['guests'])) {
            $qb->andWhere('r.capacity >= :guests')->setParameter('guests', $c['guests']);
        }
        foreach (array_values($c['amenities'] ?? []) as $i => $amenityId) {
            $qb->andWhere(":am$i MEMBER OF r.amenities")->setParameter("am$i", (int) $amenityId);
        }
        if (!empty($c['checkIn']) && !empty($c['checkOut'])) {
            // Exclut les chambres ayant une réservation qui chevauche la période demandée
            $sub = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(Reservation::class, 'res')
                ->andWhere('res.room = r')
                ->andWhere('res.status IN (:blocking)')
                ->andWhere('res.checkIn < :out AND res.checkOut > :in');
            $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())))
               ->setParameter('blocking', Reservation::BLOCKING_STATUSES)
               ->setParameter('in', $c['checkIn'], 'date_immutable')
               ->setParameter('out', $c['checkOut'], 'date_immutable');
        }

        match ($c['sort'] ?? 'recommended') {
            'price_asc' => $qb->orderBy('r.pricePerNight', 'ASC'),
            'price_desc' => $qb->orderBy('r.pricePerNight', 'DESC'),
            'capacity' => $qb->orderBy('r.capacity', 'DESC'),
            'newest' => $qb->orderBy('r.createdAt', 'DESC'),
            default => $qb->orderBy('r.isFeatured', 'DESC')->addOrderBy('r.pricePerNight', 'ASC'),
        };

        return $qb;
    }

    public function adminQuery(?string $q, ?int $categoryId): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->addSelect('c')
            ->join('r.category', 'c')
            ->orderBy('r.id', 'DESC');
        if ($q) {
            $qb->andWhere('LOWER(r.name) LIKE :q')->setParameter('q', '%'.mb_strtolower(addcslashes($q, '%_')).'%');
        }
        if ($categoryId) {
            $qb->andWhere('c.id = :cid')->setParameter('cid', $categoryId);
        }
        return $qb;
    }

    /** @return array{min: float, max: float} */
    public function priceRange(): array
    {
        $r = $this->createQueryBuilder('r')
            ->select('MIN(r.pricePerNight) AS minP, MAX(r.pricePerNight) AS maxP')
            ->andWhere('r.isActive = true')
            ->getQuery()->getSingleResult();
        return ['min' => (float) ($r['minP'] ?? 0), 'max' => (float) ($r['maxP'] ?? 0)];
    }
}
