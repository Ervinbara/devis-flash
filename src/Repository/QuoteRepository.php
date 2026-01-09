<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Quote;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quote>
 */
class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    /**
     * Trouve tous les devis d'un utilisateur, triés par date de création (plus récents en premier)
     */
    public function findByUserOrderedByDate(User $user): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.user = :user')
            ->setParameter('user', $user)
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de devis d'un utilisateur
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le total HT de tous les devis d'un utilisateur
     */
    public function getTotalHtByUser(User $user): float
    {
        $result = $this->createQueryBuilder('q')
            ->select('SUM(q.totalHt)')
            ->where('q.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0.0;
    }

    /**
     * Trouve les derniers devis d'un utilisateur
     */
    public function findRecentByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.user = :user')
            ->setParameter('user', $user)
            ->orderBy('q.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}