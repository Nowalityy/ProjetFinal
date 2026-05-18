<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('USER_MANAGE')"),
        new Get(security: "is_granted('USER_MANAGE')"),
        new Post(security: "is_granted('USER_MANAGE')", denormalizationContext: ['groups' => ['user:write']]),
        new Patch(security: "is_granted('USER_MANAGE')", denormalizationContext: ['groups' => ['user:write']]),
        new Delete(security: "is_granted('USER_MANAGE')"),
    ],
    normalizationContext: ['groups' => ['user:read']],
)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'createdAt'], arguments: ['orderParameterName' => 'order'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'alert:embed'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:write', 'alert:embed'])]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $password;

    /** @var list<string> ROLE_* */
    #[ORM\Column(type: 'json')]
    #[Groups(['user:read', 'user:write'])]
    private array $roles = [];

    #[ORM\Column]
    #[Groups(['user:read'])]
    private \DateTimeImmutable $createdAt;

    /**
     * Transitoire pour API (jamais persisté tel quel).
     */
    #[Groups(['user:write'])]
    private ?string $plainPassword = null;

    /** @var Collection<int, Alert> */
    #[ORM\OneToMany(targetEntity: Alert::class, mappedBy: 'assignedUser')]
    private Collection $assignedAlerts;

    /** @var Collection<int, AlertComment> */
    #[ORM\OneToMany(targetEntity: AlertComment::class, mappedBy: 'author')]
    private Collection $comments;

    public function __construct()
    {
        $this->assignedAlerts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /** @inheritdoc */
    public function getRoles(): array
    {
        return array_values(array_unique($this->roles));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    /** @inheritdoc */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /** @inheritdoc */
    public function getUserIdentifier(): string
    {
        return $this->email;
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

    /**
     * @return Collection<int, Alert>
     */
    public function getAssignedAlerts(): Collection
    {
        return $this->assignedAlerts;
    }

    /** @return Collection<int, AlertComment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }
}
