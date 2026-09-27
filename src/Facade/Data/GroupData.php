<?php

declare(strict_types=1);

namespace Matodo\Auth\Facade\Data;

/** Data z formuláře skupiny */
final class GroupData
{
    public string $name;
    public ?string $description = null;

    /** @var int[] */
    public array $permissions = [];
}
