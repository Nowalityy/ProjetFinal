<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BackedEnumFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\AuthStatus;
use App\Repository\AuthLogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: 'is_granted("ROLE_AUDITEUR")'),
        new Get(security: 'is_granted("ROLE_AUDITEUR")'),
    ],
    normalizationContext: ['groups' => ['log:read']],
)]
#[ApiFilter(BackedEnumFilter::class, properties: ['status'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt'])]
#[ORM\Entity(repositoryClass: AuthLogRepository::class)]
#[ORM\Table(name: 'auth_logs')]
class AuthLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['log:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 45)]
    #[Groups(['log:read'])]
    private string $ip;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['log:read'])]
    private ?string $userAgent = null;

    #[ORM\Column(length: 255)]
    #[Groups(['log:read'])]
    private string $emailHash;

    #[ORM\Column(enumType: AuthStatus::class, length: 10)]
    #[Groups(['log:read'])]
    private AuthStatus $status;

    #[ORM\Column]
    #[Groups(['log:read'])]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Alert> */
    #[ORM\OneToMany(targetEntity: Alert::class, mappedBy: 'authLog', cascade: [])]
    private Collection $alerts;

    public function __construct()
    {
        $this->alerts = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getEmailHash(): string
    {
        return $this->emailHash;
    }

    public function setEmailHash(string $emailHash): static
    {
        $this->emailHash = $emailHash;

        return $this;
    }

    public function getStatus(): AuthStatus
    {
        return $this->status;
    }

    public function setStatus(AuthStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** @return Collection<int, Alert> */
    public function getAlerts(): Collection
    {
        return $this->alerts;
    }

    public function isFailure(): bool
    {
        return AuthStatus::Failed === $this->status || AuthStatus::Blocked === $this->status;
    }
}
