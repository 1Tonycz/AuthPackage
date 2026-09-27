<?php

declare(strict_types=1);

namespace Matodo\Auth\Security;

use Doctrine\ORM\EntityManagerInterface;
use Matodo\Auth\Entity\Group;

final class DoctrineAclSource implements AclSource
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function loadAcl(): array
    {
        $acl = [];

        foreach ($this->em->getRepository(Group::class)->findAll() as $group) {
            $acl[$group->getName()] = [];

            foreach ($group->getPermissions() as $permission) {
                // neaktivní oprávnění (odinstalovaný balíček) se nepočítá
                if ($permission->isActive()) {
                    $acl[$group->getName()][] = $permission->getKey();
                }
            }
        }

        return $acl;
    }
}
