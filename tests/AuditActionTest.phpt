<?php

declare(strict_types=1);

use Matodo\Auth\Facade\AuditAction;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

// vlastní akce auth balíčku mají český popis
Assert::same('Přihlášení', AuditAction::labelOf('user.login'));
Assert::same('Změna oprávnění skupiny', AuditAction::labelOf('group.set_permissions'));

// akce z jiných balíčků (news apod.) zůstanou jako kód
Assert::same('news.create', AuditAction::labelOf('news.create'));

// hodnoty enumu musí sedět se staršími záznamy v DB
Assert::same('user.change_password', AuditAction::UserChangePassword->value);
Assert::same('group.delete', AuditAction::GroupDelete->value);

// každá akce má popis
foreach (AuditAction::cases() as $action) {
    Assert::notSame('', $action->label());
}
