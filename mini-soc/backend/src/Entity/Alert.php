<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BackedEnumFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Enum\AlertSeverity;
use App\Enum\AlertStatus;
use App\Enum\AlertType;
use App\Repository\AlertRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: 'is_granted("ROLE_AUDITEUR")'),
        new Get(security: "is_granted('ALERT_VIEW', object)"),
        new Patch(security: "is_granted('ALERT_EDIT', object)", denormalizationContext: ['groups' => ['alert:patch']]),
        new Delete(security: "is_granted('ALERT_DELETE', object)"),
    ],
    normalizationContext: ['groups' => ['alert:read']],
)]
#[ApiFilter(BackedEnumFilter::class, properties: ['type', 'severity', 'status'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt', 'resolvedAt'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'severity', 'status'])]
#[ORM\Entity(repositoryClass: AlertRepository::class)]
#[ORM\Table(name: 'alerts')]
class Alert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['alert:read'])]
    private ?int $id = null;

    #[ORM\Column(enumType: AlertType::class, length: 50)]
    #[Groups(['alert:read'])]
    private AlertType $type;

    #[ORM\Column(enumType: AlertSeverity::class, length: 10)]
    #[Groups(['alert:read'])]
    private AlertSeverity $severity;

    #[ORM\Column(enumType: AlertStatus::class, length: 20, options: ['default' => 'open'])]
    #[Groups(['alert:read', 'alert:patch'])]
    private AlertStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['alert:read', 'alert:patch'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['alert:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['alert:read'])]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\ManyToOne(inversedBy: 'assignedAlerts')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['alert:read', 'alert:patch'])]
    private ?User $assignedUser = null;

    #[ORM\ManyToOne(inversedBy: 'alerts')]
    #[ORM\JoinColumn(name: 'auth_log_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['alert:read'])]
    private AuthLog $authLog;

    /** @var Collection<int, AlertComment> */
    #[ORM\OneToMany(targetEntity: AlertComment::class, mappedBy: 'alert', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['alert:read'])]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->status = AlertStatus::Open;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): AlertType
    {
        return $this->type;
    }

    public function setType(AlertType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSeverity(): AlertSeverity
    {
        return $this->severity;
    }

    public function setSeverity(AlertSeverity $severity): static
    {
        $this->severity = $severity;

        return $this;
    }

    public function getStatus(): AlertStatus
    {
        return $this->status;
    }

    public function setStatus(AlertStatus $status): static
    {
        $this->status = $status;

        if (AlertStatus::Resolved === $status) {
            if (null === $this->resolvedAt) {
                $this->resolvedAt = new \DateTimeImmutable();
            }
        } else {
            $this->resolvedAt = null;
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }

    public function getAssignedUser(): ?User
    {
        return $this->assignedUser;
    }

    public function setAssignedUser(?User $assignedUser): static
    {
        $this->assignedUser = $assignedUser;

        return $this;
    }

    public function assignTo(User $assignedUser): void
    {
        $this->assignedUser = $assignedUser;
    }

    public function getAuthLog(): AuthLog
    {
        return $this->authLog;
    }

    public function setAuthLog(AuthLog $authLog): static
    {
        $this->authLog = $authLog;

        return $this;
    }

    public function resolve(): void
    {
        $this->setStatus(AlertStatus::Resolved);
    }

    /** @return Collection<int, AlertComment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }
}
