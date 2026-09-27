<?php

declare(strict_types=1);

namespace Matodo\Auth\Facade;

use Doctrine\ORM\EntityManagerInterface;
use Matodo\Auth\Entity\Group;
use Matodo\Auth\Entity\Permission;
use Matodo\Auth\Facade\Data\GroupData;

final class GroupFacade
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function findAll(): array
    {
        return $this->em->getRepository(Group::class)->findBy([], ['name' => 'ASC']);
    }

    public function get(int $id): ?Group
    {
        return $this->em->find(Group::class, $id);
    }

    public function getByName(string $name): ?Group
    {
        return $this->em->getRepository(Group::class)->findOneBy(['name' => $name]);
    }

    public function isNameTaken(string $name, ?int $exceptId = null): bool
    {
        $group = $this->getByName($name);

        return $group && $group->getId() !== $exceptId;
    }

    /** Uloží novou ($group = null) nebo upravenou skupinu i s oprávněními, v jedné transakci */
    public function save(?Group $group, GroupData $data, ?int $actorId = null): Group
    {
        return $this->em->wrapInTransaction(function () use ($group, $data, $actorId): Group {
            $isNew = !$group;

            if ($isNew) {
                $group = new Group($data->name, $data->description);
                $this->em->persist($group);
            } else {
                $group->setName($data->name);
                $group->setDescription($data->description);
            }

            $this->assignPermissions($group, $data->permissions);
            $this->em->flush();

            $this->auditLogger->log($actorId, $isNew ? AuditAction::GroupCreate : AuditAction::GroupUpdate, 'group', $group->getId(), [
                'name' => $group->getName(),
                'permissions' => $this->permissionKeys($group),
            ]);

            return $group;
        });
    }

    public function create(string $name, ?string $description = null, bool $isSystem = false, ?int $actorId = null): Group
    {
        $group = new Group($name, $description, $isSystem);
        $this->em->persist($group);
        $this->em->flush();

        $this->auditLogger->log($actorId, AuditAction::GroupCreate, 'group', $group->getId(), ['name' => $name]);

        return $group;
    }

    public function update(Group $group, ?int $actorId = null): void
    {
        $this->em->flush();
        $this->auditLogger->log($actorId, AuditAction::GroupUpdate, 'group', $group->getId(), ['name' => $group->getName()]);
    }

    public function delete(Group $group, ?int $actorId = null): void
    {
        if ($group->isSystem()) {
            throw new \LogicException('Systémovou skupinu nelze smazat.');
        }

        $id = $group->getId();
        $name = $group->getName();

        $this->em->remove($group);
        $this->em->flush();

        $this->auditLogger->log($actorId, AuditAction::GroupDelete, 'group', $id, ['name' => $name]);
    }

    /** Přepíše oprávnění skupiny podle zadaných ID */
    public function setPermissions(Group $group, array $permissionIds, ?int $actorId = null): void
    {
        $this->assignPermissions($group, $permissionIds);
        $this->em->flush();

        $this->auditLogger->log($actorId, AuditAction::GroupSetPermissions, 'group', $group->getId(), [
            'permissions' => $this->permissionKeys($group),
        ]);
    }

    private function assignPermissions(Group $group, array $permissionIds): void
    {
        $group->clearPermissions();

        $permissions = $permissionIds ? $this->em->getRepository(Permission::class)->findBy(['id' => $permissionIds]) : [];
        foreach ($permissions as $permission) {
            $group->addPermission($permission);
        }
    }

    private function permissionKeys(Group $group): array
    {
        $keys = [];
        foreach ($group->getPermissions() as $permission) {
            $keys[] = $permission->getKey();
        }

        return $keys;
    }
}
