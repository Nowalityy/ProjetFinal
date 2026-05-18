<?php

declare(strict_types=1);

namespace App\Enum;

enum AuthStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Blocked = 'blocked';

    /**
     * @phpstan-return list<non-falsy-string>
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }
}
