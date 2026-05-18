<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuthLog;
use App\Enum\AlertSeverity;
use App\Enum\AlertType;
use App\Repository\AlertRepository;
use App\Repository\AuthLogRepository;

/**
 * Règles de détection (Jalon 4) ; délégation pure vers AlertService (SRP).
 */
class DetectionService
{
    public function __construct(
        private AuthLogRepository $authLogRepository,
        private AlertRepository $alertRepository,
        private AlertService $alertService,
        private AbuseIPDBClient $abuseIpClient,
    ) {
    }

    /** >= 5 échecs même IP dans 10 minutes. */
    public function checkBruteForce(string $ip): bool
    {
        return $this->authLogRepository->countFailedAttemptsByIp($ip, 10) >= 5;
    }

    /** >= 3 identifiants distincts en échec, même IP, fenêtre de 5 minutes. */
    public function checkCredentialStuffing(string $ip): bool
    {
        return $this->authLogRepository->countDistinctEmailsByIp($ip, 5) >= 3;
    }

    /** Score AbuseIPDB > 75 (avec cache TTL 24 h). */
    public function checkIpReputation(string $ip): bool
    {
        try {
            return $this->abuseIpClient->check($ip)['score'] > 75;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Anomalie géographique — MVP sans historique utilisateur enrichi → toujours false.
     *
     * @SuppressWarnings Could Have : corrélations pays utilisateur / GeoIP résident
     */
    public function analyzeGeolocation(string $ip, int $userId): bool
    {
        unset($ip, $userId);

        return false;
    }

    /** Connexion hors plage jour (22 h — 06 h UTC). */
    public function isNightActivity(?\DateTimeInterface $when = null): bool
    {
        $when ??= new \DateTimeImmutable();
        $hour = (int) $when->format('G');

        return $hour >= 22 || $hour < 6;
    }

    /** Orchestration des règles pour un événement d'authentification. */
    public function analyze(AuthLog $log): void
    {
        $ip = $log->getIp();

        // --- Réputation IP (toutes tentative) ---
        try {
            if ($this->checkIpReputation($ip)) {
                if (! $this->alertRepository->hasRecentOpenDuplicate(AlertType::MaliciousIp, $ip)) {
                    $alert = $this->alertService->createAlert(
                        AlertType::MaliciousIp,
                        AlertSeverity::High,
                        $log,
                        'Score AbuseIPDB > 75 ou cache malveillant pour cette IP.',
                    );
                    $this->alertService->notifyAdmin($alert);
                }
            }
        } catch (\Throwable $e) {
            // Fallback silencieux (API indisponible)
            unset($e);
        }

        // --- Tentatives ratées ---
        if ($log->isFailure()) {
            if ($this->checkCredentialStuffing($ip)
                && ! $this->alertRepository->hasRecentOpenDuplicate(AlertType::CredentialStuffing, $ip)) {
                $alert = $this->alertService->createAlert(
                    AlertType::CredentialStuffing,
                    AlertSeverity::High,
                    $log,
                    'Nombreux identifiants testés depuis la même IP (< 5 min).',
                );
                $this->alertService->notifyAdmin($alert);
            }

            if ($this->checkBruteForce($ip)) {
                if (! $this->alertRepository->hasRecentOpenDuplicate(AlertType::BruteForce, $ip)) {
                    $alert = $this->alertService->createAlert(
                        AlertType::BruteForce,
                        AlertSeverity::High,
                        $log,
                        'Nombre d\'échecs consécutifs dépassé le seuil (10 min).',
                    );
                    $this->alertService->notifyAdmin($alert);
                    $this->alertService->blockIp($ip, 15);
                }
            }
        } else {
            // Activités nocturnes sur succès légitimes
            if ($this->isNightActivity($log->getCreatedAt())) {
                $alert = $this->alertService->createAlert(
                    AlertType::NightActivity,
                    AlertSeverity::Low,
                    $log,
                    'Connexion en dehors des heures métier.',
                );
                $this->alertService->notifyAdmin($alert);
            }
        }
    }
}
