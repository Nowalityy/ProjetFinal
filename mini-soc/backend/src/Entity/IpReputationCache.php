<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\IpReputationCacheRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IpReputationCacheRepository::class)]
#[ORM\Table(name: 'ip_reputation_cache')]
#[ORM\UniqueConstraint(name: 'uniq_ip_reputation_ip', columns: ['ip'])]
class IpReputationCache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 45)]
    private string $ip;

    #[ORM\Column(type: 'smallint')]
    private int $score;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $isp = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isTor = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isVpn = false;

    #[ORM\Column]
    private \DateTimeImmutable $lastChecked;

    public function __construct()
    {
        $this->lastChecked = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getIsp(): ?string
    {
        return $this->isp;
    }

    public function setIsp(?string $isp): static
    {
        $this->isp = $isp;

        return $this;
    }

    public function isTor(): bool
    {
        return $this->isTor;
    }

    public function setTor(bool $isTor): static
    {
        $this->isTor = $isTor;

        return $this;
    }

    public function isVpn(): bool
    {
        return $this->isVpn;
    }

    public function setVpn(bool $isVpn): static
    {
        $this->isVpn = $isVpn;

        return $this;
    }

    public function getLastChecked(): \DateTimeImmutable
    {
        return $this->lastChecked;
    }

    public function setLastChecked(\DateTimeImmutable $lastChecked): static
    {
        $this->lastChecked = $lastChecked;

        return $this;
    }

    /**
     * Vrai lorsque la cache est périmée (plus ancienne que le TTL défini).
     */
    public function isExpired(int $ttlHours = 24): bool
    {
        $deadline = (new \DateTimeImmutable(sprintf('-%d hours', $ttlHours)))->getTimestamp();

        return $this->lastChecked->getTimestamp() <= $deadline;
    }

    public function isMalicious(): bool
    {
        return $this->score > 75;
    }
}
