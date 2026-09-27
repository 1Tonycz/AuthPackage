<?php

declare(strict_types=1);

namespace Matodo\Auth\Console;

use Matodo\Auth\Facade\PermissionFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SyncPermissionsCommand extends Command
{
    /** @param array $providers všechny služby PermissionProvider (předá AuthExtension) */
    public function __construct(
        private readonly PermissionFacade $permissionFacade,
        private readonly array $providers,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('auth:sync-permissions')
            ->setDescription('Nahraje do DB oprávnění deklarovaná balíčky (PermissionProvider)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->permissionFacade->sync($this->providers);

        $output->writeln(sprintf(
            '<info>Hotovo: přidáno %d, deaktivováno %d, znovu aktivováno %d (poskytovatelů: %d).</info>',
            $result['added'],
            $result['deactivated'],
            $result['reactivated'],
            count($this->providers)
        ));

        return Command::SUCCESS;
    }
}
