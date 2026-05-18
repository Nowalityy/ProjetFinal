<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\IpReputationCache;
use App\Repository\IpReputationCacheRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client HTTP AbuseIPDB + cache en base ; quota journalier en SQL (alternative à Redis côté PHP).
 */
class AbuseIPDBClient
{
    final public const API_URL = 'https://api.abuseipdb.com/api/v2/check';

    private const QUOTA_DAILY = 1000;

    public function __construct(
        private HttpClientInterface $httpClient,
        private IpReputationCacheRepository $repository,
        private EntityManagerInterface $entityManager,
        private Connection $connection,
        #[Autowire(env: 'ABUSEIPDB_API_KEY')]
        private string $abuseIpApiKey,
    ) {
    }

    /** @return array{abuseConfidenceScore:int,countryCode:?string,isp:?string,isTor:bool,isVpn:bool,score:int,source?:string} */
    public function check(string $ip): array
    {
        $cached = $this->repository->findValidByIp($ip);
        if ($cached instanceof IpReputationCache) {
            return $this->mapEntity($cached) + ['source' => 'cache'];
        }

        if ($this->isQuotaExceeded()) {
            return $this->neutral('quota');
        }

        if ('' === $this->abuseIpApiKey) {
            return $this->neutral('no-api-key');
        }

        try {
            $response = $this->httpClient->request('GET', self::API_URL, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Key' => $this->abuseIpApiKey,
                ],
                'query' => [
                    'ipAddress' => $ip,
                    'maxAgeInDays' => '90',
                ],
                'timeout' => 4,
            ]);

            /** @phpstan-var array<string, mixed>|null $decoded */
            $decoded = json_decode((string) $response->getContent(), true);

            if (!\is_array($decoded)) {
                return $this->neutral('api-errors');
            }

            if ([] !== ($decoded['errors'] ?? [])) {
                return $this->neutral('api-errors');
            }

            /** @var array<string, mixed> $payload */
            $payload = isset($decoded['data']) && \is_array($decoded['data']) ? $decoded['data'] : [];

            $score = isset($payload['abuseConfidenceScore']) ? (int) $payload['abuseConfidenceScore'] : 0;
            $country = isset($payload['countryCode']) ? substr((string) $payload['countryCode'], 0, 3) : null;

            $isp = isset($payload['isp']) ? (string) $payload['isp'] : null;

            $this->incrementQuotaUsage();

            $entity = new IpReputationCache();
            $entity->setIp($ip);
            $entity->setScore(min(100, max(0, $score)));
            $entity->setCountry($country);
            $entity->setIsp($isp);
            $entity->setTor(isset($payload['isTor']) && (bool) $payload['isTor']);
            $entity->setVpn(isset($payload['isVpn']) && (bool) $payload['isVpn']);
            $entity->setLastChecked(new \DateTimeImmutable());

            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->mapEntity($entity) + ['source' => 'api'];
        } catch (\Throwable) {
            return $this->neutral('exception');
        }
    }

    public function isQuotaExceeded(): bool
    {
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $cnt = $this->connection->fetchOne('SELECT cnt FROM abuseip_quota WHERE day = :day', ['day' => $today]);
        $hits = false !== $cnt && null !== $cnt ? (int) $cnt : 0;

        return $hits >= self::QUOTA_DAILY;
    }

    private function incrementQuotaUsage(): void
    {
        $today = (new \DateTimeImmutable())->format('Y-m-d');

        $this->connection->executeStatement(
            <<<SQL
            INSERT INTO abuseip_quota (day, cnt)
            VALUES (:day, 1)
            ON CONFLICT (day)
            DO UPDATE SET cnt = abuseip_quota.cnt + 1
            SQL,
            ['day' => $today],
        );
    }

    /** @return array{abuseConfidenceScore:int,countryCode:?string,isp:?string,isTor:bool,isVpn:bool,score:int,source:string} */
    private function neutral(string $reason): array
    {
        return [
            'abuseConfidenceScore' => 0,
            'countryCode' => null,
            'isp' => null,
            'isTor' => false,
            'isVpn' => false,
            'score' => 0,
            'source' => 'fallback:'.$reason,
        ];
    }

    /** @return array{abuseConfidenceScore:int,countryCode:?string,isp:?string,isTor:bool,isVpn:bool,score:int} */
    private function mapEntity(IpReputationCache $c): array
    {
        return [
            'abuseConfidenceScore' => $c->getScore(),
            'countryCode' => $c->getCountry(),
            'isp' => $c->getIsp(),
            'isTor' => $c->isTor(),
            'isVpn' => $c->isVpn(),
            'score' => $c->getScore(),
        ];
    }
}
