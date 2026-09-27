<?php

declare(strict_types=1);

namespace Matodo\Auth\Facade\Data;

/** Data z formuláře uživatele - Nette na ně formulář namapuje samo podle typů */
final class UserData
{
    public string $username;
    public string $email;

    // prázdné = při úpravě nechat původní heslo
    public string $password = '';
    public bool $active = true;

    /** @var int[] */
    public array $groups = [];
}
