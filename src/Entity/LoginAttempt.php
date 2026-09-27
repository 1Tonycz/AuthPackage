<?php

declare(strict_types=1);

namespace Matodo\Auth\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth_login_attempt')]
#[ORM\Index(name: 'idx_username_ip_created', columns: ['username', 'ip', 'created_at'])]
class LoginAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 100)]
    private string $username;

    #[ORM\Column(length: 45)]
    private string $ip;

    #[ORM\Column]
    private bool $success;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $username, string $ip, bool $success)
    {
        $this->username = $username;
        $this->ip = $ip;
        $this->success = $success;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
