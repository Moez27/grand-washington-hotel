<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /** Re-hache automatiquement le mot de passe si l'algorithme évolue */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }

    public function adminQuery(?string $q, ?string $role, ?string $state): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u')->orderBy('u.createdAt', 'DESC');
        if ($q) {
            $qb->andWhere('LOWER(u.email) LIKE :q OR LOWER(u.firstName) LIKE :q OR LOWER(u.lastName) LIKE :q OR u.phone LIKE :q')
               ->setParameter('q', '%'.mb_strtolower(addcslashes($q, '%_')).'%');
        }
        if ('admin' === $role) {
            $qb->andWhere('u.roles LIKE :r')->setParameter('r', '%ROLE_ADMIN%');
        } elseif ('client' === $role) {
            $qb->andWhere('u.roles NOT LIKE :r')->setParameter('r', '%ROLE_ADMIN%');
        }
        if ('blocked' === $state) {
            $qb->andWhere('u.isBlocked = true');
        } elseif ('active' === $state) {
            $qb->andWhere('u.isBlocked = false');
        }
        return $qb;
    }

    public function countClients(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.roles NOT LIKE :r')->setParameter('r', '%ROLE_ADMIN%')
            ->getQuery()->getSingleScalarResult();
    }

    /** @return User[] */
    public function newestClients(int $limit = 5): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles NOT LIKE :r')->setParameter('r', '%ROLE_ADMIN%')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /** @return \DateTimeImmutable[] */
    public function registrationDates(\DateTimeImmutable $since): array
    {
        return array_column($this->createQueryBuilder('u')
            ->select('u.createdAt')
            ->andWhere('u.createdAt >= :s')->setParameter('s', $since)
            ->getQuery()->getArrayResult(), 'createdAt');
    }
}
