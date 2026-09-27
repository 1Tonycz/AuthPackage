<?php

declare(strict_types=1);

use Matodo\Auth\Security\AclSource;
use Matodo\Auth\Security\DbAuthorizator;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

final class FakeAclSource implements AclSource
{
    public int $loadCount = 0;

    public function __construct(private array $acl)
    {
    }

    public function loadAcl(): array
    {
        $this->loadCount++;
        return $this->acl;
    }
}

$source = new FakeAclSource([
    'redaktori' => ['news:view', 'news:create', 'news:edit'],
    'spravci-uzivatelu' => ['user:view', 'user:edit'],
]);
$authorizator = new DbAuthorizator($source);

// člen skupiny smí to, co má skupina
Assert::true($authorizator->isAllowed('redaktori', 'news', 'view'));
Assert::true($authorizator->isAllowed('redaktori', 'news', 'edit'));

// a nic jiného
Assert::false($authorizator->isAllowed('redaktori', 'news', 'publish'));
Assert::false($authorizator->isAllowed('redaktori', 'user', 'view'));

// neznámá skupina -> nic
Assert::false($authorizator->isAllowed('neexistuje', 'news', 'view'));

// skupina admin smí vše
Assert::true($authorizator->isAllowed('admin', 'news', 'publish'));
Assert::true($authorizator->isAllowed('admin', 'cokoliv', 'cokoliv'));

// ACL se načte jen jednou za request
Assert::true($authorizator->isAllowed('spravci-uzivatelu', 'user', 'view'));
Assert::same(1, $source->loadCount);
