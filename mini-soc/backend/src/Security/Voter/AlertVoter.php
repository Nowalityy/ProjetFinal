<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Alert;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * RBAC sur les alertes : auditeur lecture seule, analyste peut éditer, admin peut tout.
 */
final class AlertVoter extends Voter
{
    public const VIEW = 'ALERT_VIEW';

    public const EDIT = 'ALERT_EDIT';

    public const DELETE = 'ALERT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Alert && \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Alert) {
            return false;
        }

        $user = $token->getUser();
        if (!\is_object($user)) {
            return false;
        }

        $roles = $token->getRoleNames();

        switch ($attribute) {
            case self::VIEW:
                return $this->hasAny($roles, ['ROLE_AUDITEUR', 'ROLE_ANALYSTE', 'ROLE_ADMIN']);

            case self::EDIT:
                return $this->hasAny($roles, ['ROLE_ANALYSTE', 'ROLE_ADMIN']);

            case self::DELETE:
                return $this->hasAny($roles, ['ROLE_ADMIN']);
        }

        return false;
    }

    /** @param list<string> $roles */
    private function hasAny(array $roles, array $wanted): bool
    {
        foreach ($wanted as $r) {
            if (\in_array($r, $roles, true)) {
                return true;
            }
        }

        return false;
    }
}
