<?php

declare(strict_types=1);

namespace Matodo\Auth\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth_group')]
class Group
{
    // členové téhle skupiny smí vše
    public const Superuser = 'admin';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 100, unique: true)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // systémovou skupinu (admin) nejde smazat
    #[ORM\Column(name: 'is_system')]
    private bool $system = false;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'groups')]
    private Collection $users;

    #[ORM\ManyToMany(targetEntity: Permission::class)]
    #[ORM\JoinTable(name: 'auth_group_permission')]
    #[ORM\JoinColumn(name: 'group_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'permission_id', onDelete: 'CASCADE')]
    private Collection $permissions;

    public function __construct(string $name, ?string $description = null, bool $system = false)
    {
        $this->name = $name;
        $this->description = $description;
        $this->system = $system;
        $this->users = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isSystem(): bool
    {
        return $this->system;
    }

    public function isSuperuser(): bool
    {
        return $this->name === self::Superuser;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): void
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
        }
    }

    public function removePermission(Permission $permission): void
    {
        $this->permissions->removeElement($permission);
    }

    public function clearPermissions(): void
    {
        $this->permissions->clear();
    }
}
