<?php

declare(strict_types=1);

namespace Matodo\Auth\Facade;

use Doctrine\ORM\EntityManagerInterface;
use Matodo\Auth\Entity\LoginAttempt;

final class DoctrineAttemptStorage implements AttemptStorage
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function record(string $username, string $ip, bool $success, \DateTimeImmutable $at): void
    {
        $this->em->persist(new LoginAttempt($username, $ip, $success));
        $this->em->flush();
    }

    public function countRecentFailures(string $username, string $ip, \DateTimeImmutable $since): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(LoginAttempt::class, 'a')
            ->where('a.username = :username AND a.ip = :ip AND a.success = false AND a.createdAt >= :since')
            ->setParameter('username', $username)
            ->setParameter('ip', $ip)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function lastFailureAt(string $username, string $ip): ?\DateTimeImmutable
    {
        $attempt = $this->em->createQueryBuilder()
            ->select('a')
            ->from(LoginAttempt::class, 'a')
            ->where('a.username = :username AND a.ip = :ip AND a.success = false')
            ->setParameter('username', $username)
            ->setParameter('ip', $ip)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $attempt?->getCreatedAt();
    }

    public function purgeOlderThan(\DateTimeImmutable $threshold): void
    {
        $this->em->createQueryBuilder()
            ->delete(LoginAttempt::class, 'a')
            ->where('a.createdAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }
}
