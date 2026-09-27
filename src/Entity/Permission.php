<?php

declare(strict_types=1);

namespace Matodo\Auth\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth_permission')]
#[ORM\UniqueConstraint(name: 'uniq_resource_privilege', columns: ['resource', 'privilege'])]
class Permission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 100)]
    private string $resource;

    #[ORM\Column(length: 100)]
    private string $privilege;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    // ze kterého balíčku oprávnění pochází, např. matodo/news
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $package = null;

    // false = balíček už oprávnění nedeklaruje (řádek se nemaže, aby zůstaly vazby na skupiny)
    #[ORM\Column(name: 'is_active')]
    private bool $active = true;

    public function __construct(string $resource, string $privilege, ?string $description = null, ?string $package = null)
    {
        $this->resource = $resource;
        $this->privilege = $privilege;
        $this->description = $description;
        $this->package = $package;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getPrivilege(): string
    {
        return $this->privilege;
    }

    /** Klíč ve tvaru "resource:privilege" - tak se oprávnění porovnávají v ACL i při synchronizaci */
    public function getKey(): string
    {
        return $this->resource . ':' . $this->privilege;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getPackage(): ?string
    {
        return $this->package;
    }

    public function setPackage(?string $package): void
    {
        $this->package = $package;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
