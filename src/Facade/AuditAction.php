<?php

declare(strict_types=1);

namespace Matodo\Auth\Facade;

/**
 * Akce, které do historie změn zapisuje auth balíček.
 * Ostatní balíčky můžou dál posílat vlastní akce jako string (např. 'news.create').
 */
enum AuditAction: string
{
    case UserLogin = 'user.login';
    case UserLogout = 'user.logout';
    case UserCreate = 'user.create';
    case UserUpdate = 'user.update';
    case UserChangePassword = 'user.change_password';
    case UserSetGroups = 'user.set_groups';
    case UserDelete = 'user.delete';
    case GroupCreate = 'group.create';
    case GroupUpdate = 'group.update';
    case GroupSetPermissions = 'group.set_permissions';
    case GroupDelete = 'group.delete';

    public function label(): string
    {
        return match ($this) {
            self::UserLogin => 'Přihlášení',
            self::UserLogout => 'Odhlášení',
            self::UserCreate => 'Nový uživatel',
            self::UserUpdate => 'Úprava uživatele',
            self::UserChangePassword => 'Změna hesla',
            self::UserSetGroups => 'Změna skupin uživatele',
            self::UserDelete => 'Smazání uživatele',
            self::GroupCreate => 'Nová skupina',
            self::GroupUpdate => 'Úprava skupiny',
            self::GroupSetPermissions => 'Změna oprávnění skupiny',
            self::GroupDelete => 'Smazání skupiny',
        };
    }

    /** Pro šablonu - u cizích akcí vrátí jen jejich kód */
    public static function labelOf(string $action): string
    {
        return self::tryFrom($action)?->label() ?? $action;
    }
}
