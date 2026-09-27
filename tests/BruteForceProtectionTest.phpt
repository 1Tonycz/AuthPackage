<?php

declare(strict_types=1);

use Matodo\Auth\Facade\AttemptStorage;
use Matodo\Auth\Facade\BruteForceProtection;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

final class InMemoryAttemptStorage implements AttemptStorage
{
    public array $attempts = [];

    public function record(string $username, string $ip, bool $success, \DateTimeImmutable $at): void
    {
        $this->attempts[] = ['username' => $username, 'ip' => $ip, 'success' => $success, 'at' => $at];
    }

    public function countRecentFailures(string $username, string $ip, \DateTimeImmutable $since): int
    {
        return count(array_filter(
            $this->attempts,
            fn (array $a) => $a['username'] === $username && $a['ip'] === $ip && !$a['success'] && $a['at'] >= $since,
        ));
    }

    public function lastFailureAt(string $username, string $ip): ?\DateTimeImmutable
    {
        $failures = array_filter(
            $this->attempts,
            fn (array $a) => $a['username'] === $username && $a['ip'] === $ip && !$a['success'],
        );
        $last = null;
        foreach ($failures as $f) {
            $last = $last === null || $f['at'] > $last ? $f['at'] : $last;
        }
        return $last;
    }

    public function purgeOlderThan(\DateTimeImmutable $threshold): void
    {
        $this->attempts = array_values(array_filter($this->attempts, fn (array $a) => $a['at'] >= $threshold));
    }
}

// pod limitem -> nezamčeno
$storage = new InMemoryAttemptStorage();
$bf = new BruteForceProtection($storage, maxAttempts: 5, lockMinutes: 15);
$now = new DateTimeImmutable('2026-07-09 12:00:00');

for ($i = 0; $i < 4; $i++) {
    $bf->recordAttempt('honza', '1.2.3.4', false, $now);
}
Assert::null($bf->isLocked('honza', '1.2.3.4', $now));

// limit dosažen -> zamčeno do posledního pokusu + lockMinutes
$bf->recordAttempt('honza', '1.2.3.4', false, $now);
$unlockAt = $bf->isLocked('honza', '1.2.3.4', $now);
Assert::type(DateTimeImmutable::class, $unlockAt);
Assert::equal(new DateTimeImmutable('2026-07-09 12:15:00'), $unlockAt);

// jiná IP nebo jiné jméno zamčené není
Assert::null($bf->isLocked('honza', '5.6.7.8', $now));
Assert::null($bf->isLocked('pepa', '1.2.3.4', $now));

// po uplynutí doby -> zase odemčeno
$later = new DateTimeImmutable('2026-07-09 12:15:01');
Assert::null($bf->isLocked('honza', '1.2.3.4', $later));

// úspěšné pokusy se do limitu nepočítají
$storage2 = new InMemoryAttemptStorage();
$bf2 = new BruteForceProtection($storage2, maxAttempts: 2, lockMinutes: 15);
$bf2->recordAttempt('eva', '9.9.9.9', true, $now);
$bf2->recordAttempt('eva', '9.9.9.9', true, $now);
Assert::null($bf2->isLocked('eva', '9.9.9.9', $now));
