<?php

declare(strict_types=1);

namespace Matodo\Auth\Presentation\Users;

use Matodo\Auth\Entity\User;
use Matodo\Auth\Facade\Data\UserData;
use Matodo\Auth\Facade\UserFacade;
use Matodo\Auth\Presentation\BaseAdminPresenter;
use Matodo\Auth\Security\RequiresPermission;
use Nette\Application\UI\Form;

#[RequiresPermission('user', 'view')]
final class UsersPresenter extends BaseAdminPresenter
{
    private ?User $editedUser = null;

    public function __construct(
        private readonly UserFacade $userFacade,
        private readonly UserFormFactory $userFormFactory,
    ) {}

    public function renderDefault(): void
    {
        $this->template->users = $this->userFacade->findAll();
    }

    #[RequiresPermission('user', 'create')]
    public function actionCreate(): void
    {
    }

    #[RequiresPermission('user', 'edit')]
    public function actionEdit(int $id): void
    {
        $this->editedUser = $this->userFacade->get($id) ?? $this->error('Uživatel nenalezen.');
        $this->template->editedUser = $this->editedUser;
    }

    protected function createComponentUserForm(): Form
    {
        $form = $this->userFormFactory->create($this->editedUser);

        // Nette formulář samo namapuje na UserData podle typu parametru
        $form->onSuccess[] = function (Form $form, UserData $data): void {
            $this->userFacade->save($this->editedUser, $data, $this->getUser()->getId());

            $this->flashMessage($this->editedUser ? 'Uživatel byl upraven.' : 'Uživatel byl vytvořen.', 'success');
            $this->redirect('default');
        };

        return $form;
    }

    #[RequiresPermission('user', 'delete')]
    public function handleDelete(int $id): void
    {
        $user = $this->userFacade->get($id) ?? $this->error('Uživatel nenalezen.');

        if ($user->getId() === $this->getUser()->getId()) {
            $this->flashMessage('Nemůžete smazat sám sebe.', 'error');
            $this->redirect('default');
        }

        $this->userFacade->delete($user, $this->getUser()->getId());
        $this->flashMessage('Uživatel byl smazán.', 'success');
        $this->redirect('default');
    }
}
