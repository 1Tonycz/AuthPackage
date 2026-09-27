<?php

declare(strict_types=1);

namespace Matodo\Auth\Facade;

/**
 * Ochrana proti hádání hesel. Když má kombinace jméno + IP za posledních lockMinutes
 * aspoň maxAttempts neúspěšných pokusů, zamkne se na lockMinutes od posledního pokusu.
 */
final class BruteForceProtection
{
    public function __construct(
        private readonly AttemptStorage $storage,
        private readonly int $maxAttempts = 5,
        private readonly int $lockMinutes = 15,
    ) {}

    /** Vrací čas odemčení, nebo null když se přihlásit může */
    public function isLocked(string $username, string $ip, ?\DateTimeImmutable $now = null): ?\DateTimeImmutable
    {
        $now = $now ?? new \DateTimeImmutable();

        $since = $now->modify('-' . $this->lockMinutes . ' minutes');
        if ($this->storage->countRecentFailures($username, $ip, $since) < $this->maxAttempts) {
            return null;
        }

        $lastFailure = $this->storage->lastFailureAt($username, $ip);
        if (!$lastFailure) {
            return null;
        }

        $unlockAt = $lastFailure->modify('+' . $this->lockMinutes . ' minutes');

        return $unlockAt > $now ? $unlockAt : null;
    }

    public function recordAttempt(string $username, string $ip, bool $success, ?\DateTimeImmutable $now = null): void
    {
        $this->storage->record($username, $ip, $success, $now ?? new \DateTimeImmutable());
    }

    /** Smaže pokusy starší než 24 hodin */
    public function purgeOld(?\DateTimeImmutable $now = null): void
    {
        $now = $now ?? new \DateTimeImmutable();
        $this->storage->purgeOlderThan($now->modify('-24 hours'));
    }
}
