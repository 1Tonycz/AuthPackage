<?php

declare(strict_types=1);

namespace Matodo\Auth\DI;

/** Nastavení z configu (sekce auth:) pro presentery */
final class AuthConfig
{
    public function __construct(
        public string $rememberMeExpiration = '14 days',
        public ?string $layoutFile = null,
        public ?string $signTemplateFile = null,
    ) {}
}
