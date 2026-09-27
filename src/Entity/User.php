<?php

declare(strict_types=1);

namespace Matodo\Auth\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth_user')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 100, unique: true)]
    private string $username;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column(name: 'password_hash', length: 255)]
    private string $passwordHash;

    #[ORM\Column(name: 'is_active')]
    private bool $active = true;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'last_login_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'auth_user_group')]
    #[ORM\JoinColumn(name: 'user_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'group_id', onDelete: 'CASCADE')]
    private Collection $groups;

    public function __construct(string $username, string $email, string $passwordHash)
    {
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->createdAt = new \DateTimeImmutable();
        $this->groups = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): void
    {
        $this->lastLoginAt = $lastLoginAt;
    }

    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): void
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }
    }

    public function removeGroup(Group $group): void
    {
        $this->groups->removeElement($group);
    }

    public function clearGroups(): void
    {
        $this->groups->clear();
    }

    /** Názvy skupin - používají se jako role v Nette identitě */
    public function getGroupNames(): array
    {
        $names = [];
        foreach ($this->groups as $group) {
            $names[] = $group->getName();
        }

        return $names;
    }
}
