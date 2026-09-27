# matodo/auth

Uživatelé, skupiny a oprávnění (model Windows security groups) s hotovou administrací pro Nette aplikace.

## Funkce

- Uživatelé a skupiny (M:N), oprávnění pouze přes skupiny
- Skupina `admin` = superuser, systémové skupiny nelze smazat
- `PermissionProvider` interface — jakýkoliv balíček deklaruje svá oprávnění, `auth:sync-permissions` je synchronizuje do DB
- Zabezpečení presenterů: `#[RequiresPermission('resource', 'privilege')]` + trait `SecuredPresenter`
- Brute-force ochrana (staré pokusy se mažou při přihlášení), historie změn, remember me
- Role se načítají z DB při každém requestu (`IdentityHandler`) - změna skupin platí hned, zablokovaný uživatel je odhlášen
- Admin UI: přihlášení, správa uživatelů, skupin (matice oprávnění), historie změn

## Instalace

```json
"repositories": [{ "type": "vcs", "url": "https://github.com/<org>/matodo-auth" }],
"require": { "matodo/auth": "^1.0" }
```

```neon
extensions:
    auth: Matodo\Auth\DI\AuthExtension

application:
    mapping:
        Auth: Matodo\Auth\Presentation\*\**Presenter

orm:
    managers:
        default:
            mapping:
                MatodoAuth:
                    type: attributes
                    namespace: Matodo\Auth\Entity
                    directories: [%appDir%/../vendor/matodo/auth/src/Entity]

migrations:
    directories:
        Matodo\Auth\Migrations: %appDir%/../vendor/matodo/auth/src/Migrations
```

## Konfigurace (výchozí hodnoty)

```neon
auth:
    bruteForce:
        maxAttempts: 5
        lockMinutes: 15
    rememberMe:
        expiration: '14 days'
    layoutFile: null    # vlastní admin layout
```

## První spuštění

```
bin/console migrations:migrate
bin/console auth:create-admin <username> <email> <password>
bin/console auth:sync-permissions
```

## Deklarace oprávnění ve vlastním balíčku

```php
final class MyPermissions implements Matodo\Auth\Security\PermissionProvider
{
    public function getPermissions(): array
    {
        return ['invoice' => ['view' => 'Zobrazení faktur', 'edit' => 'Úprava faktur']];
    }
}
```

Službu zaregistrujte v DI — auth ji najde automaticky.

## Zabezpečení presenteru

Atribut jde dát na třídu, na `action*`/`render*` metodu i na `handle*` signál presenteru.

```php
use Matodo\Auth\Security\RequiresPermission;
use Matodo\Auth\Security\SecuredPresenter;

#[RequiresPermission('invoice', 'view')]
final class InvoicePresenter extends Nette\Application\UI\Presenter
{
    use SecuredPresenter;

    #[RequiresPermission('invoice', 'delete')]
    public function handleDelete(int $id): void
    {
    }
}
```

Signály komponent (`komponenta-signal!`) se atributy nekontrolují, tam oprávnění hlídejte v komponentě.

## Historie změn z vlastního kódu

```php
$auditLogger->log($userId, 'invoice.create', 'invoice', $invoice->getId(), ['number' => $number]);
```

Akce auth balíčku jsou v enumu `AuditAction` a v administraci se zobrazují česky.

## Testy

```
composer install
vendor/bin/tester tests -s
```
