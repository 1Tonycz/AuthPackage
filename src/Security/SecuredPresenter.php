<?php

declare(strict_types=1);

namespace Matodo\Auth\Security;

use Nette\Application\UI\Presenter;

/**
 * Nepřihlášeného pošle na přihlášení, přihlášenému bez oprávnění vrátí 403.
 * Kontroluje #[RequiresPermission] na třídě, na action/render metodě
 * a na handle metodě signálu presenteru (např. delete!).
 * Signály komponent si oprávnění hlídají samy.
 *
 * @mixin Presenter
 */
trait SecuredPresenter
{
    public function startup(): void
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect(':Auth:Sign:in', ['backlink' => $this->storeRequest()]);
        }

        foreach ($this->getRequiredPermissions() as $permission) {
            if (!$this->getUser()->isAllowed($permission->resource, $permission->privilege)) {
                $this->error('Nemáte oprávnění.', 403);
            }
        }
    }

    private function getRequiredPermissions(): array
    {
        $class = new \ReflectionClass($this);
        $attributes = $class->getAttributes(RequiresPermission::class);

        $methods = [
            static::formatActionMethod($this->getAction()),
            static::formatRenderMethod($this->getView()),
        ];

        // signál je známý už před startup(), prázdné jméno komponenty = signál samotného presenteru
        $signal = $this->getSignal();
        if ($signal && $signal[0] === '') {
            $methods[] = static::formatSignalMethod($signal[1]);
        }

        foreach ($methods as $method) {
            if ($class->hasMethod($method)) {
                $attributes = [...$attributes, ...$class->getMethod($method)->getAttributes(RequiresPermission::class)];
            }
        }

        return array_map(fn (\ReflectionAttribute $attribute) => $attribute->newInstance(), $attributes);
    }
}
