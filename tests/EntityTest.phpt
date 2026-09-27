<?php

declare(strict_types=1);

use Matodo\Auth\Entity\Group;
use Matodo\Auth\Entity\Permission;
use Matodo\Auth\Entity\User;
use Matodo\Auth\Facade\PermissionFacade;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

// klíč oprávnění
$permission = new Permission('news', 'edit', 'Úprava novinek', 'matodo/news');
Assert::same('news:edit', $permission->getKey());

// superuser se pozná podle názvu skupiny
Assert::true((new Group(Group::Superuser))->isSuperuser());
Assert::false((new Group('redaktori'))->isSuperuser());

// skupina se uživateli nepřidá dvakrát, názvy skupin = role
$user = new User('honza', 'honza@example.com', 'hash');
$editors = new Group('redaktori');
$user->addGroup($editors);
$user->addGroup($editors);
$user->addGroup(new Group('spravci'));
Assert::same(['redaktori', 'spravci'], $user->getGroupNames());

// název balíčku z namespace provideru
Assert::same('matodo/news', PermissionFacade::packageNameFromClass('Matodo\News\Security\NewsPermissions'));
Assert::same('app/security', PermissionFacade::packageNameFromClass('App\Security\AppPermissions'));
Assert::same('permissions', PermissionFacade::packageNameFromClass('Permissions'));
