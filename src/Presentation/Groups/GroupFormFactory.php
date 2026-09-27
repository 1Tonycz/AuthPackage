<?php

declare(strict_types=1);

namespace Matodo\Auth\Presentation\Groups;

use Matodo\Auth\Entity\Group;
use Matodo\Auth\Facade\GroupFacade;
use Matodo\Auth\Facade\PermissionFacade;
use Nette\Application\UI\Form;

final class GroupFormFactory
{
    public function __construct(
        private readonly PermissionFacade $permissionFacade,
        private readonly GroupFacade $groupFacade,
    ) {}

    public function create(?Group $group = null): Form
    {
        $form = new Form();
        $form->addProtection('Platnost formuláře vypršela, odešlete ho prosím znovu.');

        $name = $form->addText('name', 'Název skupiny:')
            ->setRequired('Zadejte název skupiny.')
            ->addRule($form::MaxLength, 'Název může mít maximálně %d znaků.', 100);

        $form->addTextArea('description', 'Popis:')
            ->setNullable();

        // checkboxy se v šabloně vykreslují ručně jako matice podle balíčků
        $permissions = [];
        foreach ($this->permissionFacade->findAllGroupedByPackage() as $packagePermissions) {
            foreach ($packagePermissions as $permission) {
                $permissions[$permission->getId()] = $permission->getKey();
            }
        }

        $form->addCheckboxList('permissions', 'Oprávnění:', $permissions);

        $form->addSubmit('send', $group ? 'Uložit' : 'Vytvořit skupinu');

        $form->onValidate[] = function () use ($group, $name): void {
            if ($this->groupFacade->isNameTaken($name->getValue(), $group?->getId())) {
                $name->addError('Skupina s tímto názvem už existuje.');
            }
        };

        if ($group) {
            $form->setDefaults([
                'name' => $group->getName(),
                'description' => $group->getDescription(),
                'permissions' => $group->getPermissions()->map(fn ($permission) => $permission->getId())->getValues(),
            ]);
        }

        return $form;
    }
}
