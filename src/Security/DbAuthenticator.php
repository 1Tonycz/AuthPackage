<?php

declare(strict_types=1);

namespace Matodo\Auth\Security;

use Doctrine\ORM\EntityManagerInterface;
use Matodo\Auth\Entity\User;
use Matodo\Auth\Facade\AuditAction;
use Matodo\Auth\Facade\AuditLogger;
use Matodo\Auth\Facade\BruteForceProtection;
use Nette\Http\IRequest;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\IdentityHandler;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

/**
 * Přihlášení proti tabulce auth_user.
 * Jako IdentityHandler si do session ukládá jen ID a při každém requestu načte
 * uživatele znovu - změna skupin se tak projeví hned a zablokovaný uživatel je odhlášen.
 */
final class DbAuthenticator implements Authenticator, IdentityHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BruteForceProtection $bruteForce,
        private readonly AuditLogger $auditLogger,
        private readonly IRequest $httpRequest,
        private readonly Passwords $passwords,
    ) {}

    public function authenticate(string $user, string $password): SimpleIdentity
    {
        $ip = $this->httpRequest->getRemoteAddress() ?? 'unknown';

        $unlockAt = $this->bruteForce->isLocked($user, $ip);
        if ($unlockAt) {
            throw new AuthenticationException('Příliš mnoho neúspěšných pokusů. Zkuste to znovu po ' . $unlockAt->format('H:i') . '.', self::Failure);
        }

        $row = $this->em->getRepository(User::class)->findOneBy(['username' => $user]);

        // schválně stejná hláška pro všechny případy, ať nejde zjistit, jestli účet existuje
        if (!$row || !$row->isActive() || !$this->passwords->verify($password, $row->getPasswordHash())) {
            $this->bruteForce->recordAttempt($user, $ip, false);
            throw new AuthenticationException('Nesprávné přihlašovací údaje.', self::Failure);
        }

        if ($this->passwords->needsRehash($row->getPasswordHash())) {
            $row->setPasswordHash($this->passwords->hash($password));
        }

        $row->setLastLoginAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->bruteForce->recordAttempt($user, $ip, true);
        // úklid starých pokusů - při přihlášení stačí, nepotřebujeme na to cron
        $this->bruteForce->purgeOld();
        $this->auditLogger->log($row->getId(), AuditAction::UserLogin);

        return $this->createIdentity($row);
    }

    public function sleepIdentity(IIdentity $identity): IIdentity
    {
        // do session jen ID, role a údaje se při dalším requestu načtou z DB
        return new SimpleIdentity($identity->getId());
    }

    public function wakeupIdentity(IIdentity $identity): ?IIdentity
    {
        $user = $this->em->find(User::class, $identity->getId());

        // smazaný nebo zablokovaný uživatel = null = Nette ho odhlásí
        if (!$user || !$user->isActive()) {
            return null;
        }

        return $this->createIdentity($user);
    }

    private function createIdentity(User $user): SimpleIdentity
    {
        // role = názvy skupin
        return new SimpleIdentity(
            $user->getId(),
            $user->getGroupNames(),
            ['username' => $user->getUsername(), 'email' => $user->getEmail()]
        );
    }
}
