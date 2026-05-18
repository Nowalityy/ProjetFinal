<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\IpReputationCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IpReputationCache>
 */
class IpReputationCacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IpReputationCache::class);
    }

    public function findValidByIp(string $ip): ?IpReputationCache
    {
        $entry = $this->findOneBy(['ip' => $ip]);

        return $entry instanceof IpReputationCache && ! $entry->isExpired()
            ? $entry
            : null;
    }
}
