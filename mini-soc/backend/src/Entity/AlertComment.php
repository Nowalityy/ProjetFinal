<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\AlertCommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: 'is_granted("ROLE_AUDITEUR")'),
        new Get(security: 'is_granted("ROLE_AUDITEUR")'),
        new Post(security: 'is_granted("ROLE_ANALYSTE")', denormalizationContext: ['groups' => ['comment:write']]),
    ],
    normalizationContext: ['groups' => ['comment:read']],
)]
#[ORM\Entity(repositoryClass: AlertCommentRepository::class)]
#[ORM\Table(name: 'alert_comments')]
class AlertComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['comment:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['comment:read', 'comment:write'])]
    private string $content;

    #[ORM\Column]
    #[Groups(['comment:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'alert_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['comment:read', 'comment:write'])]
    private Alert $alert;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['comment:read'])]
    private User $author;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

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

    public function getAlert(): Alert
    {
        return $this->alert;
    }

    public function setAlert(Alert $alert): static
    {
        $this->alert = $alert;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): static
    {
        $this->author = $author;

        return $this;
    }
}
