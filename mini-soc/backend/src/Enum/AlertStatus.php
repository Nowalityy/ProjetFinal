<?php

declare(strict_types=1);

namespace App\Enum;

enum AlertStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case FalsePositive = 'false_positive';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }
}
