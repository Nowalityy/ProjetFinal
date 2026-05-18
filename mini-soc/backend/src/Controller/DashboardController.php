<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AlertRepository;
use App\Repository\AuthLogRepository;
use App\Repository\IpReputationCacheRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Agrégats pour les widgets CDC §3.5 (un appel SPA).
 */
#[Route('/api/dashboard')]
final class DashboardController extends AbstractController
{
    #[Route('/stats', name: 'api_dashboard_stats', methods: ['GET'])]
    public function stats(
        AuthLogRepository $authLogRepository,
        AlertRepository $alertRepository,
        IpReputationCacheRepository $ipReputationRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_AUDITEUR');

        $authSeries = [];
        foreach ($authLogRepository->authAttemptsPerHourLast7Days() as $bucket) {
            $authSeries[] = [
                'hour' => $bucket['bucket'],
                'attempts' => $bucket['count'],
            ];
        }

        $bySeverity = $alertRepository->alertsBySeverityLast30Days();

        $top = [];
        foreach ($authLogRepository->topIpsByAttempts() as $row) {
            $rep = $ipReputationRepository->findValidByIp($row['ip']);
            $top[] = [
                'ip' => $row['ip'],
                'attempts' => $row['attempts'],
                'failures' => $row['failures'],
                'failureRate' => $row['failureRate'],
                'country' => $rep?->getCountry(),
                'score' => $rep?->getScore(),
            ];
        }

        $severityStats = [];
        foreach ($bySeverity as $row) {
            $severityStats[] = [
                'severity' => $row['severity'],
                'count' => $row['count'],
            ];
        }

        return $this->json([
            'counters' => [
                'activeAlerts' => $alertRepository->countActive(),
                'authAttempts24h' => $authLogRepository->countAttemptsLast24Hours(),
                'uniqueIp24h' => $authLogRepository->countDistinctIpsLast24Hours(),
                'successRate24h' => $authLogRepository->successRateLast24Hours(),
            ],
            'charts' => [
                'attemptsHourly7d' => $authSeries,
                'alertsBySeverity30d' => $severityStats,
                'alertStatusShares' => $alertRepository->countByStatusCategories(),
            ],
            'topIps' => $top,
        ]);
    }
}
