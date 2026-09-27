<?php

declare(strict_types=1);

namespace Matodo\Auth\Facade;

/**
 * Porovná oprávnění v DB s tím, co deklarují nainstalované balíčky.
 * Nic se nemaže, jen deaktivuje - po odinstalaci balíčku tak zůstane nastavení skupin.
 */
final class SyncPlanner
{
    /**
     * @param array $current  oprávnění v DB: ['resource:privilege' => ['package' => ..., 'active' => bool]]
     * @param array $provided oprávnění z providerů: ['resource:privilege' => ['package' => ..., 'description' => ...]]
     */
    public function plan(array $current, array $provided): SyncPlan
    {
        $plan = new SyncPlan();

        foreach ($provided as $key => $definition) {
            if (!isset($current[$key])) {
                $plan->add[] = $key;
            } elseif (!$current[$key]['active']) {
                $plan->reactivate[] = $key;
            }
        }

        foreach ($current as $key => $state) {
            if ($state['active'] && !isset($provided[$key])) {
                $plan->deactivate[] = $key;
            }
        }

        return $plan;
    }
}
