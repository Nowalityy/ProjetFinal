<?php

declare(strict_types=1);

namespace App\Security;

use App\Enum\AuthStatus;
use App\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Tentative échouée : trace AuthLog et renvoie 401 JSON harmonisé avec le SPA.
 */
class JwtLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(private AuthService $authService)
    {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        unset($exception);

        $payload = [];
        $raw = json_decode((string) $request->getContent(), true);
        if (\is_array($raw)) {
            $payload = $raw;
        }

        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $emailNormalized = '' === $email ? 'unknown-login@failed.invalid' : $email;

        $this->authService->logAttempt(
            (string) $request->getClientIp(),
            $emailNormalized,
            AuthStatus::Failed,
            $request->headers->get('User-Agent'),
        );

        return new JsonResponse(
            ['code' => 401, 'message' => 'Identifiants invalides'],
            Response::HTTP_UNAUTHORIZED,
        );
    }
}
