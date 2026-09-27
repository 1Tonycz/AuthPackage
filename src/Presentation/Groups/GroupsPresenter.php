<?php

declare(strict_types=1);

namespace Matodo\Auth\Presentation\Groups;

use Matodo\Auth\Entity\Group;
use Matodo\Auth\Facade\Data\GroupData;
use Matodo\Auth\Facade\GroupFacade;
use Matodo\Auth\Facade\PermissionFacade;
use Matodo\Auth\Presentation\BaseAdminPresenter;
use Matodo\Auth\Security\RequiresPermission;
use Nette\Application\UI\Form;

#[RequiresPermission('group', 'view')]
final class GroupsPresenter extends BaseAdminPresenter
{
    private ?Group $editedGroup = null;

    public function __construct(
        private readonly GroupFacade $groupFacade,
        private readonly PermissionFacade $permissionFacade,
        private readonly GroupFormFactory $groupFormFactory,
    ) {}

    public function renderDefault(): void
    {
        $this->template->groups = $this->groupFacade->findAll();
    }

    #[RequiresPermission('group', 'create')]
    public function actionCreate(): void
    {
        $this->template->permissionsByPackage = $this->permissionFacade->findAllGroupedByPackage();
    }

    #[RequiresPermission('group', 'edit')]
    public function actionEdit(int $id): void
    {
        $this->editedGroup = $this->groupFacade->get($id) ?? $this->error('Skupina nenalezena.');
        $this->template->editedGroup = $this->editedGroup;
        $this->template->permissionsByPackage = $this->permissionFacade->findAllGroupedByPackage();
    }

    protected function createComponentGroupForm(): Form
    {
        $form = $this->groupFormFactory->create($this->editedGroup);

        $form->onSuccess[] = function (Form $form, GroupData $data): void {
            $this->groupFacade->save($this->editedGroup, $data, $this->getUser()->getId());

            $this->flashMessage($this->editedGroup ? 'Skupina byla upravena.' : 'Skupina byla vytvořena.', 'success');
            $this->redirect('default');
        };

        return $form;
    }

    #[RequiresPermission('group', 'delete')]
    public function handleDelete(int $id): void
    {
        $group = $this->groupFacade->get($id) ?? $this->error('Skupina nenalezena.');

        try {
            $this->groupFacade->delete($group, $this->getUser()->getId());
            $this->flashMessage('Skupina byla smazána.', 'success');
        } catch (\LogicException $e) {
            // systémová skupina
            $this->flashMessage($e->getMessage(), 'error');
        }

        $this->redirect('default');
    }
}
