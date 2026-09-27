<?php

declare(strict_types=1);

namespace Matodo\Auth\Security;

/**
 * Implementuje každý balíček/projekt, který má vlastní oprávnění.
 * Stačí třídu zaregistrovat jako službu, auth:sync-permissions ji najde sám.
 */
interface PermissionProvider
{
    /** Vrací pole [resource => [privilege => popis]] */
    public function getPermissions(): array;
}
