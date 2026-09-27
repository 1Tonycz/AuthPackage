<?php

declare(strict_types=1);

namespace Matodo\Auth\Security;

use Matodo\Auth\Entity\Group;
use Nette\Security\Authorizator;

final class DbAuthorizator implements Authorizator
{
    // nechané kvůli zpětné kompatibilitě, nově Group::Superuser
    public const SuperuserGroup = Group::Superuser;

    private ?array $acl = null;

    public function __construct(
        private readonly AclSource $source,
    ) {}

    public function isAllowed(string|\Stringable|null $role, string|\Stringable|null $resource, string|\Stringable|null $privilege): bool
    {
        $role = (string) $role;

        // skupina admin má vše
        if ($role === Group::Superuser) {
            return true;
        }

        // ACL se načte z DB jen jednou za request
        if ($this->acl === null) {
            $this->acl = [];
            foreach ($this->source->loadAcl() as $group => $permissions) {
                $this->acl[$group] = array_flip($permissions);
            }
        }

        return isset($this->acl[$role][$resource . ':' . $privilege]);
    }
}
