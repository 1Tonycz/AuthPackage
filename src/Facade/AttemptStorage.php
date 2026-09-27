<?php

declare(strict_types=1);

namespace Matodo\Auth\Facade;

/** Úložiště pokusů o přihlášení (interface kvůli testům bez DB) */
interface AttemptStorage
{
    public function record(string $username, string $ip, bool $success, \DateTimeImmutable $at): void;

    public function countRecentFailures(string $username, string $ip, \DateTimeImmutable $since): int;

    public function lastFailureAt(string $username, string $ip): ?\DateTimeImmutable;

    public function purgeOlderThan(\DateTimeImmutable $threshold): void;
}
