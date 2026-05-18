<?php

declare(strict_types=1);

namespace App\Enum;

enum AlertType: string
{
    case BruteForce = 'brute_force';
    case CredentialStuffing = 'credential_stuffing';
    case MaliciousIp = 'malicious_ip';
    case GeoAnomaly = 'geo_anomaly';
    case NightActivity = 'night_activity';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }
}
