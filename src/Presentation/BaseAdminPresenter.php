<?php

declare(strict_types=1);

namespace Matodo\Auth\Presentation;

use Matodo\Auth\DI\AuthConfig;
use Matodo\Auth\Security\SecuredPresenter;
use Nette\Application\UI\Presenter;

abstract class BaseAdminPresenter extends Presenter
{
    use SecuredPresenter;

    protected AuthConfig $authConfig;

    public function injectAuthConfig(AuthConfig $authConfig): void
    {
        $this->authConfig = $authConfig;
    }

    // projekt si může v configu nastavit vlastní layout (auth: layoutFile:)
    public function formatLayoutTemplateFiles(): array
    {
        return [$this->authConfig->layoutFile ?? __DIR__ . '/@layout.latte'];
    }
}
