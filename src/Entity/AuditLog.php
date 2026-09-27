<?php

declare(strict_types=1);

namespace Matodo\Auth\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth_audit_log')]
#[ORM\Index(name: 'idx_audit_created', columns: ['created_at'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\Column(length: 100)]
    private string $action;

    #[ORM\Column(name: 'subject_type', length: 100, nullable: true)]
    private ?string $subjectType;

    #[ORM\Column(name: 'subject_id', nullable: true)]
    private ?int $subjectId;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(?User $user, string $action, ?string $subjectType = null, ?int $subjectId = null, ?array $data = null)
    {
        $this->user = $user;
        $this->action = $action;
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
        $this->data = $data;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getSubjectType(): ?string
    {
        return $this->subjectType;
    }

    public function getSubjectId(): ?int
    {
        return $this->subjectId;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
