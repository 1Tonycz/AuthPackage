<?php

declare(strict_types=1);

namespace Matodo\Auth\Facade;

use Doctrine\ORM\EntityManagerInterface;
use Matodo\Auth\Entity\AuditLog;
use Matodo\Auth\Entity\User;

final class AuditLogger
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function log(?int $userId, AuditAction|string $action, ?string $subjectType = null, ?int $subjectId = null, ?array $data = null): void
    {
        // přihlášený uživatel už je načtený z wakeupIdentity, takže find() jde z paměti
        $user = $userId ? $this->em->find(User::class, $userId) : null;

        if ($action instanceof AuditAction) {
            $action = $action->value;
        }

        $this->em->persist(new AuditLog($user, $action, $subjectType, $subjectId, $data));
        $this->em->flush();
    }
}
