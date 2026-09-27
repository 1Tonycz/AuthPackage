<?php

declare(strict_types=1);

namespace Matodo\Auth\Security;

final class AuthPermissions implements PermissionProvider
{
    public function getPermissions(): array
    {
        return [
            'user' => [
                'view' => 'Zobrazení uživatelů',
                'create' => 'Vytváření uživatelů',
                'edit' => 'Úprava uživatelů',
                'delete' => 'Mazání uživatelů',
            ],
            'group' => [
                'view' => 'Zobrazení skupin',
                'create' => 'Vytváření skupin',
                'edit' => 'Úprava skupin a oprávnění',
                'delete' => 'Mazání skupin',
            ],
            'audit' => [
                'view' => 'Zobrazení audit logu',
            ],
        ];
    }
}
