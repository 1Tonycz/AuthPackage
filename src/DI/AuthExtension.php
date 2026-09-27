<?php

declare(strict_types=1);

namespace Matodo\Auth\DI;

use Matodo\Auth\Console\CreateAdminCommand;
use Matodo\Auth\Console\SyncPermissionsCommand;
use Matodo\Auth\Facade\AuditLogger;
use Matodo\Auth\Facade\BruteForceProtection;
use Matodo\Auth\Facade\DoctrineAttemptStorage;
use Matodo\Auth\Facade\GroupFacade;
use Matodo\Auth\Facade\PermissionFacade;
use Matodo\Auth\Facade\SyncPlanner;
use Matodo\Auth\Facade\UserFacade;
use Matodo\Auth\Presentation\Groups\GroupFormFactory;
use Matodo\Auth\Presentation\Users\UserFormFactory;
use Matodo\Auth\Security\AuthPermissions;
use Matodo\Auth\Security\DbAuthenticator;
use Matodo\Auth\Security\DbAuthorizator;
use Matodo\Auth\Security\DoctrineAclSource;
use Matodo\Auth\Security\PermissionProvider;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class AuthExtension extends CompilerExtension
{
    /** @var \stdClass config po zpracování schématem (Expect::structure vrací stdClass) */
    protected $config;

    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'bruteForce' => Expect::structure([
                'maxAttempts' => Expect::int(5),
                'lockMinutes' => Expect::int(15),
            ]),
            'rememberMe' => Expect::structure([
                'expiration' => Expect::string('14 days'),
            ]),
            'layoutFile' => Expect::string()->nullable(),
            'signTemplateFile' => Expect::string()->nullable(),
        ]);
    }

    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
        $config = $this->config;

        $builder->addDefinition($this->prefix('config'))
            ->setFactory(AuthConfig::class, [
                $config->rememberMe->expiration,
                $config->layoutFile,
                $config->signTemplateFile,
            ]);

        $builder->addDefinition($this->prefix('bruteForce'))
            ->setFactory(BruteForceProtection::class, [
                'maxAttempts' => $config->bruteForce->maxAttempts,
                'lockMinutes' => $config->bruteForce->lockMinutes,
            ]);

        // ostatní služby nepotřebují žádné parametry
        $services = [
            'attemptStorage' => DoctrineAttemptStorage::class,
            'auditLogger' => AuditLogger::class,
            'aclSource' => DoctrineAclSource::class,
            'authorizator' => DbAuthorizator::class,
            'authenticator' => DbAuthenticator::class,
            'userFacade' => UserFacade::class,
            'groupFacade' => GroupFacade::class,
            'syncPlanner' => SyncPlanner::class,
            'permissionFacade' => PermissionFacade::class,
            'authPermissions' => AuthPermissions::class,
            'userFormFactory' => UserFormFactory::class,
            'groupFormFactory' => GroupFormFactory::class,
        ];

        foreach ($services as $name => $class) {
            $builder->addDefinition($this->prefix($name))
                ->setFactory($class);
        }

        $builder->addDefinition($this->prefix('createAdminCommand'))
            ->setFactory(CreateAdminCommand::class)
            ->addTag('console.command', 'auth:create-admin');
    }

    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();

        // až tady jsou zaregistrované služby ze všech extensions, takže najdeme i providery jiných balíčků
        $providers = array_values($builder->findByType(PermissionProvider::class));

        $builder->addDefinition($this->prefix('syncPermissionsCommand'))
            ->setFactory(SyncPermissionsCommand::class, [
                'providers' => $providers,
            ])
            ->addTag('console.command', 'auth:sync-permissions');
    }
}
