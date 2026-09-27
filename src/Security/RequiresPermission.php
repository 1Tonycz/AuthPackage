<?php

declare(strict_types=1);

namespace Matodo\Auth\Security;

/**
 * Oprávnění potřebné pro celý presenter (na třídě) nebo jednu akci (na action/render metodě).
 * Kontroluje ho trait SecuredPresenter.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class RequiresPermission
{
    public function __construct(
        public string $resource,
        public string $privilege,
    ) {}
}
