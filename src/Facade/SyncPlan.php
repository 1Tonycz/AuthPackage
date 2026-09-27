<?php

declare(strict_types=1);

namespace Matodo\Auth\Facade;

/** Výsledek SyncPlanneru - klíče jsou ve tvaru "resource:privilege" */
final class SyncPlan
{
    public function __construct(
        public array $add = [],
        public array $deactivate = [],
        public array $reactivate = [],
    ) {}
}
