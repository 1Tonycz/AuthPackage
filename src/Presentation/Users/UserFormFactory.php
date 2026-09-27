<?php

declare(strict_types=1);

namespace Matodo\Auth\Presentation\Users;

use Matodo\Auth\Entity\User;
use Matodo\Auth\Facade\GroupFacade;
use Matodo\Auth\Facade\UserFacade;
use Nette\Application\UI\Form;

final class UserFormFactory
{
    public function __construct(
        private readonly GroupFacade $groupFacade,
        private readonly UserFacade $userFacade,
    ) {}

    public function create(?User $user = null): Form
    {
        $form = new Form();
        $form->addProtection('Platnost formuláře vypršela, odešlete ho prosím znovu.');

        $username = $form->addText('username', 'Uživatelské jméno:')
            ->setRequired('Zadejte uživatelské jméno.')
            ->addRule($form::MaxLength, 'Uživatelské jméno může mít maximálně %d znaků.', 100);

        $email = $form->addEmail('email', 'E-mail:')
            ->setRequired('Zadejte e-mail.');

        // při úpravě je heslo nepovinné
        $password = $form->addPassword('password', $user ? 'Nové heslo (nevyplňujte, pokud ho nechcete měnit):' : 'Heslo:');
        if (!$user) {
            $password->setRequired('Zadejte heslo.');
        }
        $password->addCondition($form::Filled)
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků.', 8);

        $form->addCheckbox('active', 'Aktivní účet')
            ->setDefaultValue(true);

        $groups = [];
        foreach ($this->groupFacade->findAll() as $group) {
            $groups[$group->getId()] = $group->getName();
        }

        $form->addMultiSelect('groups', 'Skupiny:', $groups);

        $form->addSubmit('send', $user ? 'Uložit' : 'Vytvořit uživatele');

        // unikátnost hlídáme tady, ať uživatel dostane hlášku u pole a ne chybu z DB
        $form->onValidate[] = function () use ($user, $username, $email): void {
            if ($this->userFacade->isUsernameTaken($username->getValue(), $user?->getId())) {
                $username->addError('Toto uživatelské jméno už někdo používá.');
            }

            if ($this->userFacade->isEmailTaken($email->getValue(), $user?->getId())) {
                $email->addError('Tento e-mail už někdo používá.');
            }
        };

        if ($user) {
            $form->setDefaults([
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'active' => $user->isActive(),
                'groups' => $user->getGroups()->map(fn ($group) => $group->getId())->getValues(),
            ]);
        }

        return $form;
    }
}
