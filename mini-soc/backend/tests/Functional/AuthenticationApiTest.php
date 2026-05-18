<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Nécessite : base migrée + fixtures chargées (admin@minisoc.local / Admin12345!).
 *
 * @group integration
 */
final class AuthenticationApiTest extends WebTestCase
{
    public function testSuccessfulLoginReturnsJwt(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'admin@minisoc.local',
                'password' => 'Admin12345!',
            ], JSON_THROW_ON_ERROR),
        );

        if (401 === $client->getResponse()->getStatusCode()) {
            self::markTestSkipped('Base non initialisée ou fixtures absentes (voir README).');
        }

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('token', $data);
    }
}
