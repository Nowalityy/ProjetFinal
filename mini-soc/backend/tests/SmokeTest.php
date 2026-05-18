<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase
{
    public function testPhpEnvironment(): void
    {
        self::assertTrue(\extension_loaded('pdo'));
    }
}
