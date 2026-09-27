<?php

declare(strict_types=1);

namespace Matodo\Auth\Console;

use Matodo\Auth\Entity\Group;
use Matodo\Auth\Facade\Data\UserData;
use Matodo\Auth\Facade\GroupFacade;
use Matodo\Auth\Facade\UserFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Nette\Utils\Validators;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserFacade $userFacade,
        private readonly GroupFacade $groupFacade,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('auth:create-admin')
            ->setDescription('Vytvoří uživatele ve skupině admin (skupinu založí, pokud neexistuje)')
            ->addArgument('username', InputArgument::REQUIRED, 'Uživatelské jméno')
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail')
            ->addArgument('password', InputArgument::REQUIRED, 'Heslo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = (string) $input->getArgument('username');
        $email = (string) $input->getArgument('email');
        $password = (string) $input->getArgument('password');

        if ($this->userFacade->isUsernameTaken($username)) {
            $output->writeln('<error>Uživatel "' . $username . '" už existuje.</error>');
            return Command::FAILURE;
        }

        if (!Validators::isEmail($email)) {
            $output->writeln('<error>"' . $email . '" není platný e-mail.</error>');
            return Command::FAILURE;
        }

        if ($this->userFacade->isEmailTaken($email)) {
            $output->writeln('<error>E-mail "' . $email . '" už někdo používá.</error>');
            return Command::FAILURE;
        }

        $adminGroup = $this->groupFacade->getByName(Group::Superuser)
            ?? $this->groupFacade->create(Group::Superuser, 'Systémová skupina s plnými právy', isSystem: true);

        $data = new UserData();
        $data->username = $username;
        $data->email = $email;
        $data->password = $password;
        $data->groups = [$adminGroup->getId()];

        $this->userFacade->save(null, $data);

        $output->writeln('<info>Admin "' . $username . '" byl vytvořen ve skupině ' . Group::Superuser . '.</info>');
        return Command::SUCCESS;
    }
}
