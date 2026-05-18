<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;

/**
 * Le routeur doit connaître POST /api/login ; l’authentification JSON est prise en charge par json_login
 * (firewall « login » dans security.yaml), pas par cette méthode.
 */
final class ApiLoginController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function __invoke(): never
    {
        throw new \LogicException('POST /api/login doit être intercepté par json_login (Authenticator).');
    }
}
