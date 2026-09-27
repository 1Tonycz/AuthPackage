<?php

declare(strict_types=1);

namespace Matodo\Auth\Facade;

use Doctrine\ORM\EntityManagerInterface;
use Matodo\Auth\Entity\Permission;

final class PermissionFacade
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SyncPlanner $planner,
    ) {}

    /** Oprávnění seskupená podle balíčku - pro matici v administraci */
    public function findAllGroupedByPackage(): array
    {
        $permissions = $this->em->getRepository(Permission::class)
            ->findBy([], ['package' => 'ASC', 'resource' => 'ASC', 'privilege' => 'ASC']);

        $grouped = [];
        foreach ($permissions as $permission) {
            $grouped[$permission->getPackage() ?? 'ostatní'][] = $permission;
        }

        return $grouped;
    }

    /**
     * Srovná oprávnění v DB s tím, co deklarují providery.
     * Vrací počty ['added' => ..., 'deactivated' => ..., 'reactivated' => ...]
     */
    public function sync(iterable $providers): array
    {
        // co deklarují balíčky
        $provided = [];
        foreach ($providers as $provider) {
            $package = self::packageNameFromClass($provider::class);

            foreach ($provider->getPermissions() as $resource => $privileges) {
                foreach ($privileges as $privilege => $description) {
                    $provided[$resource . ':' . $privilege] = [
                        'package' => $package,
                        'description' => $description,
                    ];
                }
            }
        }

        // co je teď v DB
        $current = [];
        $entities = [];
        foreach ($this->em->getRepository(Permission::class)->findAll() as $permission) {
            $key = $permission->getKey();
            $current[$key] = ['package' => $permission->getPackage(), 'active' => $permission->isActive()];
            $entities[$key] = $permission;
        }

        $plan = $this->planner->plan($current, $provided);

        foreach ($plan->add as $key) {
            [$resource, $privilege] = explode(':', $key, 2);
            $this->em->persist(new Permission($resource, $privilege, $provided[$key]['description'], $provided[$key]['package']));
        }

        foreach ($plan->deactivate as $key) {
            $entities[$key]->setActive(false);
        }

        foreach ($plan->reactivate as $key) {
            $entities[$key]->setActive(true);
            $entities[$key]->setDescription($provided[$key]['description']);
            $entities[$key]->setPackage($provided[$key]['package']);
        }

        $this->em->flush();

        return [
            'added' => count($plan->add),
            'deactivated' => count($plan->deactivate),
            'reactivated' => count($plan->reactivate),
        ];
    }

    /** Z "Matodo\News\Security\NewsPermissions" udělá "matodo/news" */
    public static function packageNameFromClass(string $class): string
    {
        $parts = explode('\\', $class);

        if (count($parts) < 2) {
            return strtolower($class);
        }

        return strtolower($parts[0]) . '/' . strtolower($parts[1]);
    }
}
