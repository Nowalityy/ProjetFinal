<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Alert;
use App\Enum\AlertSeverity;
use App\Enum\AlertStatus;
use App\Enum\AlertType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Alert>
 */
class AlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alert::class);
    }

    /**
     * @return list<Alert>
     */
    public function findByStatus(AlertStatus $status): array
    {
        return $this->findBy(['status' => $status], ['createdAt' => 'DESC']);
    }

    public function countByStatusCategories(): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->createQueryBuilder('a')
            ->select('a.status AS status, COUNT(a.id) AS cnt')
            ->groupBy('a.status')
            ->getQuery()
            ->getArrayResult();

        $counts = AlertStatus::values();
        $out = array_fill_keys($counts, 0);
        foreach ($rows as $row) {
            $statusVal = $row['status'] ?? null;
            $statusStr = '';
            if ($statusVal instanceof \BackedEnum) {
                $statusStr = (string) $statusVal->value;
            } elseif (null !== $statusVal) {
                $statusStr = (string) $statusVal;
            }

            $cnt = isset($row['cnt']) ? (int) $row['cnt'] : 0;
            if ('' !== $statusStr) {
                $out[$statusStr] = $cnt;
            }
        }

        return $out;
    }

    /**
     * @return list<array{severity: string, count: int}>
     */
    public function alertsBySeverityLast30Days(): array
    {
        $threshold = new \DateTimeImmutable('-30 days');

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->createQueryBuilder('a')
            ->select('a.severity AS sev, COUNT(a.id) AS cnt')
            ->where('a.createdAt >= :t')
            ->setParameter('t', $threshold)
            ->groupBy('a.severity')
            ->getQuery()
            ->getArrayResult();

        $labels = AlertSeverity::values();
        $out = [];
        foreach ($labels as $label) {
            $out[$label] = ['severity' => $label, 'count' => 0];
        }

        foreach ($rows as $row) {
            $sevRaw = $row['sev'] ?? null;
            $sev = '';
            if ($sevRaw instanceof \BackedEnum) {
                $sev = (string) $sevRaw->value;
            } elseif (null !== $sevRaw) {
                $sev = (string) $sevRaw;
            }
            $cnt = isset($row['cnt']) ? (int) $row['cnt'] : 0;
            if ('' !== $sev && isset($out[$sev])) {
                $out[$sev]['count'] = $cnt;
            }
        }

        return array_values($out);
    }

    /** Compte alertes encore à traiter (open + in_progress). */
    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.status IN (:s)')
            ->setParameter(
                's',
                [
                    AlertStatus::Open,
                    AlertStatus::InProgress,
                ],
            )
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasRecentOpenDuplicate(AlertType $type, string $ip, string $duplicateWindowSuffix = '-2 hours'): bool
    {
        $since = new \DateTimeImmutable($duplicateWindowSuffix);

        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.authLog', 'l')
            ->where('a.type = :type')
            ->andWhere('a.status = :open')
            ->andWhere('l.ip = :ip')
            ->andWhere('a.createdAt >= :since')
            ->setParameter('type', $type)
            ->setParameter('open', AlertStatus::Open)
            ->setParameter('ip', $ip)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}
