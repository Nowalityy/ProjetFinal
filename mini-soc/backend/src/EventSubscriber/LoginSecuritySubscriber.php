<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Enum\AuthStatus;
use App\Service\AlertService;
use App\Service\AuthService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Sécurité sur /api/login : blocage Redis + fenêtre coulissante (10/min).
 */
class LoginSecuritySubscriber implements EventSubscriberInterface
{
    final public const LOGIN_ROUTE_PATH = '/api/login';

    public function __construct(
        private AuthService $authService,
        private AlertService $alertService,
        private RateLimiterFactory $loginApiLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 290],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (! $request->isMethod(Request::METHOD_POST)) {
            return;
        }

        if (self::LOGIN_ROUTE_PATH !== $request->getPathInfo()) {
            return;
        }

        $ip = (string) $request->getClientIp();

        if ($this->alertService->isIpBlocked($ip)) {
            $this->persistBlockedObservation($request, $ip);

            $event->setResponse(new JsonResponse(
                ['code' => 429, 'message' => 'IP temporairement bloquée après détection brute-force'],
                Response::HTTP_TOO_MANY_REQUESTS,
            ));

            return;
        }

        $consume = $this->loginApiLimiter->create($ip)->consume(1);

        if (! $consume->isAccepted()) {
            $this->persistBlockedObservation($request, $ip);

            $event->setResponse(new JsonResponse(
                ['code' => 429, 'message' => 'Trop de tentatives, merci de patienter'],
                Response::HTTP_TOO_MANY_REQUESTS,
            ));

            return;
        }
    }

    /** Traces RGPD sans relancer Detection (AuthService saute BLOCKED). */
    private function persistBlockedObservation(Request $request, string $ip): void
    {
        $raw = json_decode((string) $request->getContent(), true);
        $payload = \is_array($raw) ? $raw : [];

        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';

        $this->authService->logAttempt(
            $ip,
            '' === $email ? 'unknown@blocked.invalid' : $email,
            AuthStatus::Blocked,
            $request->headers->get('User-Agent'),
        );
    }
}
