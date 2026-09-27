<?php

declare(strict_types=1);

namespace Matodo\Auth\Security;

interface AclSource
{
    /** Vrací pole [název skupiny => ['resource:privilege', ...]] */
    public function loadAcl(): array;
}
