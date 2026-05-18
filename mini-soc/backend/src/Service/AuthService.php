<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuthLog;
use App\Enum\AuthStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Enregistre les tentatives d'authentification (RGPD : email haché seulement).
 */
class AuthService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DetectionService $detectionService,
    ) {
    }

    public static function hashEmail(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }

    /** Crée ou met à jour l'historique d'authentification puis lance les règles de détection. */
    public function logAttempt(string $ip, string $email, AuthStatus $status, ?string $userAgent): void
    {
        $login = new AuthLog();
        $login->setIp($ip);
        $login->setUserAgent($userAgent);
        $login->setEmailHash(self::hashEmail($email));
        $login->setStatus($status);

        $this->entityManager->persist($login);
        $this->entityManager->flush();

        if (AuthStatus::Blocked === $status) {
            return;
        }

        $this->detectionService->analyze($login);
    }
}
