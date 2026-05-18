<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Enum\AuthStatus;
use App\Service\AuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Après identification réussie : journalisation JWT + création réponse Bearer.
 */
class JwtLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private AuthService $authService,
        private JWTTokenManagerInterface $jwtTokenManager,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $identity = $token->getUser();
        if (!$identity instanceof User) {
            return new JWTAuthenticationSuccessResponse('');
        }

        $ip = (string) $request->getClientIp();
        $ua = $request->headers->get('User-Agent');
        $email = $identity->getUserIdentifier();

        $this->authService->logAttempt($ip, $email, AuthStatus::Success, $ua);

        $jwt = $this->jwtTokenManager->createFromPayload($identity, [
            'roles' => $identity->getRoles(),
        ]);

        return new JWTAuthenticationSuccessResponse($jwt);
    }
}
