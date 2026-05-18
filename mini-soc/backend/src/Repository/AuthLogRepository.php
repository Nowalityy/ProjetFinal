<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AuthLog;
use App\Enum\AuthStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthLog>
 */
class AuthLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthLog::class);
    }

    public function countFailedAttemptsByIp(string $ip, int $windowMinutes): int
    {
        $threshold = new \DateTimeImmutable(sprintf('- %d minutes', $windowMinutes));

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.ip = :ip')
            ->andWhere('a.status = :failed')
            ->andWhere('a.createdAt >= :threshold')
            ->setParameter('ip', $ip)
            ->setParameter('failed', AuthStatus::Failed)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDistinctEmailsByIp(string $ip, int $windowMinutes): int
    {
        $threshold = new \DateTimeImmutable(sprintf('- %d minutes', $windowMinutes));

        $result = $this->createQueryBuilder('a')
            ->select('COUNT(DISTINCT a.emailHash)')
            ->where('a.ip = :ip')
            ->andWhere('a.status = :failed')
            ->andWhere('a.createdAt >= :threshold')
            ->setParameter('ip', $ip)
            ->setParameter('failed', AuthStatus::Failed)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * @return list<array{bucket: string, count: int}>
     */
    public function authAttemptsPerHourLast7Days(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = <<<'SQL'
            SELECT date_trunc('hour', created_at) AT TIME ZONE 'UTC' AS bucket,
                   COUNT(*)::int AS cnt
            FROM auth_logs
            WHERE created_at >= NOW() - INTERVAL '7 days'
            GROUP BY bucket
            ORDER BY bucket ASC
            SQL;

        /** @var list<array{bucket: string, cnt: int|string}> $rows */
        $rows = $conn->executeQuery($sql)->fetchAllAssociative();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'bucket' => (string) $row['bucket'],
                'count' => (int) $row['cnt'],
            ];
        }

        return $out;
    }

    public function countAttemptsLast24Hours(): int
    {
        $threshold = new \DateTimeImmutable('-24 hours');

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.createdAt >= :t')
            ->setParameter('t', $threshold)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDistinctIpsLast24Hours(): int
    {
        $threshold = new \DateTimeImmutable('-24 hours');

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(DISTINCT a.ip)')
            ->where('a.createdAt >= :t')
            ->setParameter('t', $threshold)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function successRateLast24Hours(): float
    {
        $threshold = new \DateTimeImmutable('-24 hours');
        $qb = $this->createQueryBuilder('a');
        $total = (int) (clone $qb)
            ->select('COUNT(a.id)')
            ->where('a.createdAt >= :t')
            ->setParameter('t', $threshold)
            ->getQuery()
            ->getSingleScalarResult();
        if (0 === $total) {
            return 100.0;
        }
        $success = (int) $this->createQueryBuilder('a2')
            ->select('COUNT(a2.id)')
            ->where('a2.createdAt >= :t')
            ->andWhere('a2.status = :s')
            ->setParameter('t', $threshold)
            ->setParameter('s', AuthStatus::Success)
            ->getQuery()
            ->getSingleScalarResult();

        return round(100.0 * $success / $total, 2);
    }

    /**
     * @return list<array{ip: string, attempts: int, failures: int, failureRate: float}>
     */
    public function topIpsByAttempts(int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $safeLimit = max(1, min($limit, 100));

        $sql = <<<SQL
            SELECT ip,
                   COUNT(*)::int AS attempts,
                   SUM(CASE WHEN status = 'failed' OR status = 'blocked' THEN 1 ELSE 0 END)::int AS failures
            FROM auth_logs
            WHERE created_at >= NOW() - INTERVAL '30 days'
            GROUP BY ip
            ORDER BY attempts DESC
            LIMIT {$safeLimit}
            SQL;

        /** @var list<array{ip: string, attempts: int|string, failures: int|string}> $rows */
        $rows = $conn->fetchAllAssociative($sql);

        $out = [];
        foreach ($rows as $row) {
            $attempts = (int) $row['attempts'];
            $failures = (int) $row['failures'];
            $out[] = [
                'ip' => $row['ip'],
                'attempts' => $attempts,
                'failures' => $failures,
                'failureRate' => $attempts > 0 ? round(100 * $failures / $attempts, 2) : 0.0,
            ];
        }

        return $out;
    }
}
