<?php

declare(strict_types=1);

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Gestion utilisateurs réservée au rôle administrateur.
 */
final class UserManagementVoter extends Voter
{
    public const MANAGE = 'USER_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::MANAGE === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        unset($subject);

        return \in_array('ROLE_ADMIN', $token->getRoleNames(), true);
    }
}
