<?php

declare(strict_types=1);

namespace Matodo\Auth\Presentation\Sign;

use Matodo\Auth\DI\AuthConfig;
use Matodo\Auth\Facade\AuditAction;
use Matodo\Auth\Facade\AuditLogger;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Security\AuthenticationException;

// nedědí BaseAdminPresenter, musí být přístupný i nepřihlášeným
final class SignPresenter extends Presenter
{
    /** @persistent */
    public string $backlink = '';

    public function __construct(
        private readonly AuthConfig $authConfig,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function startup(): void
    {
        parent::startup();
        $this->setLayout(false);
    }

    // projekt si může v configu nastavit vlastní šablonu přihlášení (auth: signTemplateFile:)
    public function formatTemplateFiles(): array
    {
        $files = parent::formatTemplateFiles();

        if ($this->authConfig->signTemplateFile) {
            array_unshift($files, $this->authConfig->signTemplateFile);
        }

        return $files;
    }

    public function actionIn(): void
    {
        if ($this->getUser()->isLoggedIn()) {
            $this->redirect(':Auth:Users:default');
        }
    }

    public function actionOut(): void
    {
        if ($this->getUser()->isLoggedIn()) {
            $this->auditLogger->log($this->getUser()->getId(), AuditAction::UserLogout);
            $this->getUser()->logout(true);
        }

        $this->flashMessage('Byl jste odhlášen.', 'info');
        $this->redirect('in');
    }

    protected function createComponentSignInForm(): Form
    {
        $form = new Form();

        $form->addText('username', 'Uživatelské jméno:')
            ->setRequired('Zadejte uživatelské jméno.');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Zadejte heslo.');

        $form->addCheckbox('remember', 'Zůstat přihlášen');

        $form->addSubmit('send', 'Přihlásit se');

        $form->onSuccess[] = function (Form $form, \stdClass $values): void {
            try {
                // bez "zůstat přihlášen" vyprší přihlášení se zavřením prohlížeče
                $this->getUser()->setExpiration($values->remember ? $this->authConfig->rememberMeExpiration : '0');
                $this->getUser()->login($values->username, $values->password);
            } catch (AuthenticationException $e) {
                $form->addError($e->getMessage());
                return;
            }

            // vrátí uživatele na stránku, kam chtěl původně jít
            $this->restoreRequest($this->backlink);
            $this->redirect(':Auth:Users:default');
        };

        return $form;
    }
}
