<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Alert;
use App\Entity\AuthLog;
use App\Entity\User;
use App\Enum\AlertSeverity;
use App\Enum\AlertStatus;
use App\Enum\AlertType;
use App\Repository\AlertRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Gestion métier des alertes ; blocage IP persisté en base (PostgreSQL).
 */
class AlertService
{
    public function __construct(
        private AlertRepository $alertRepository,
        private EntityManagerInterface $entityManager,
        private Connection $connection,
        private LoggerInterface $logger,
    ) {
    }

    public function createAlert(AlertType $type, AlertSeverity $severity, AuthLog $log, string $description): Alert
    {
        $alert = new Alert();
        $alert->setType($type);
        $alert->setSeverity($severity);
        $alert->setDescription($description);
        $alert->setAuthLog($log);
        $alert->setStatus(AlertStatus::Open);

        $this->entityManager->persist($alert);
        $this->entityManager->flush();

        return $alert;
    }

    public function notifyAdmin(Alert $alert): void
    {
        $this->logger->alert(sprintf(
            '[NOTIF ADMIN] Nouvelle alerte #%d (%s / %s) — déclenchée depuis IP %s',
            $alert->getId(),
            $alert->getType()->value,
            $alert->getSeverity()->value,
            $alert->getAuthLog()->getIp(),
        ));
    }

    public function blockIp(string $ip, int $minutes): void
    {
        $expiresAt = new \DateTimeImmutable(sprintf('+ %d minutes', $minutes));

        $this->connection->executeStatement(
            <<<SQL
            INSERT INTO blocked_ips (ip, expires_at)
            VALUES (:ip, :exp)
            ON CONFLICT (ip) DO UPDATE SET expires_at = EXCLUDED.expires_at
            SQL,
            ['ip' => $ip, 'exp' => $expiresAt->format('Y-m-d H:i:s')],
        );

        $this->connection->executeStatement('DELETE FROM blocked_ips WHERE expires_at < NOW()');
    }

    public function isIpBlocked(string $ip): bool
    {
        $sql = <<<'SQL'
            SELECT COUNT(*)
            FROM blocked_ips
            WHERE ip = :ip AND expires_at > NOW()
            SQL;

        return ((int) $this->connection->fetchOne($sql, ['ip' => $ip])) > 0;
    }

    public function resolveAlert(int $id, string $comment, User $author): void
    {
        $alert = $this->alertRepository->find($id);
        if (!$alert instanceof Alert) {
            return;
        }
        $alert->resolve();

        $this->logger->notice(sprintf(
            '[ALERT RESOLVED] #%d — %s par %s (%s)',
            $id,
            $comment,
            $author->getUserIdentifier(),
            $author->getEmail(),
        ));
        $this->entityManager->flush();
    }
}
