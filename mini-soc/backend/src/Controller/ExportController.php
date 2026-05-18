<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\AuthLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Export CSV CDC §3.6 (streaming Symfony).
 */
#[Route('/api/export')]
final class ExportController extends AbstractController
{
    #[Route('/logs.csv', name: 'api_export_logs_csv', methods: ['GET'])]
    public function logsCsv(EntityManagerInterface $em): StreamedResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ANALYSTE');

        $qb = $em->createQueryBuilder()->select('l')->from(AuthLog::class, 'l')->orderBy('l.createdAt', 'ASC');

        $response = new StreamedResponse(function () use ($qb): void {
            $handle = fopen('php://output', 'w');
            if (false !== $handle) {
                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, ['id', 'ip', 'emailHash', 'status', 'createdAt', 'userAgent'], ';');

                foreach ($qb->getQuery()->toIterable() as $log) {
                    if (!$log instanceof AuthLog) {
                        continue;
                    }
                    fputcsv($handle, [
                        $log->getId(),
                        $log->getIp(),
                        $log->getEmailHash(),
                        $log->getStatus()->value,
                        $log->getCreatedAt()->format(DATE_ATOM),
                        \str_replace(["\r", "\n"], ' ', $log->getUserAgent() ?? ''),
                    ], ';');
                }

                fclose($handle);
            }
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $disp = sprintf('attachment; filename="logs-%s.csv"', (new \DateTimeImmutable())->format('Ymd_His'));
        $response->headers->set('Content-Disposition', $disp);

        return $response;
    }

    #[Route('/alerts.csv', name: 'api_export_alerts_csv', methods: ['GET'])]
    public function alertsCsv(EntityManagerInterface $em): StreamedResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ANALYSTE');

        $qb = $em->createQueryBuilder()->select('a')->from(Alert::class, 'a')->orderBy('a.createdAt', 'ASC');

        $response = new StreamedResponse(function () use ($qb): void {
            $handle = fopen('php://output', 'w');
            if (false !== $handle) {
                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, ['id', 'type', 'severity', 'status', 'description', 'assignedEmail', 'createdAt', 'resolvedAt'], ';');

                foreach ($qb->getQuery()->toIterable() as $alert) {
                    if (!$alert instanceof Alert) {
                        continue;
                    }
                    fputcsv($handle, [
                        $alert->getId(),
                        $alert->getType()->value,
                        $alert->getSeverity()->value,
                        $alert->getStatus()->value,
                        \str_replace(["\r", "\n"], ' ', $alert->getDescription() ?? ''),
                        $alert->getAssignedUser()?->getEmail(),
                        $alert->getCreatedAt()->format(DATE_ATOM),
                        null !== $alert->getResolvedAt() ? $alert->getResolvedAt()->format(DATE_ATOM) : '',
                    ], ';');
                }

                fclose($handle);
            }
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $disp = sprintf('attachment; filename="alerts-%s.csv"', (new \DateTimeImmutable())->format('Ymd_His'));
        $response->headers->set('Content-Disposition', $disp);

        return $response;
    }
}
