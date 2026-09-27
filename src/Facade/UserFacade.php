<?php

declare(strict_types=1);

namespace Matodo\Auth\Facade;

use Doctrine\ORM\EntityManagerInterface;
use Matodo\Auth\Entity\Group;
use Matodo\Auth\Entity\User;
use Matodo\Auth\Facade\Data\UserData;
use Nette\Security\Passwords;

final class UserFacade
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AuditLogger $auditLogger,
        private readonly Passwords $passwords,
    ) {}

    public function findAll(): array
    {
        return $this->em->getRepository(User::class)->findBy([], ['username' => 'ASC']);
    }

    public function get(int $id): ?User
    {
        return $this->em->find(User::class, $id);
    }

    public function getByUsername(string $username): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    /** $exceptId = při úpravě se nepočítá uživatel sám se sebou */
    public function isUsernameTaken(string $username, ?int $exceptId = null): bool
    {
        return $this->isTaken('username', $username, $exceptId);
    }

    public function isEmailTaken(string $email, ?int $exceptId = null): bool
    {
        return $this->isTaken('email', $email, $exceptId);
    }

    /**
     * Uloží nového ($user = null) nebo upraveného uživatele i se skupinami.
     * Všechno proběhne v jedné transakci a do historie jde jeden záznam.
     */
    public function save(?User $user, UserData $data, ?int $actorId = null): User
    {
        return $this->em->wrapInTransaction(function () use ($user, $data, $actorId): User {
            $isNew = !$user;

            if ($isNew) {
                $user = new User($data->username, $data->email, $this->passwords->hash($data->password));
                $this->em->persist($user);
            } else {
                $user->setUsername($data->username);
                $user->setEmail($data->email);

                if ($data->password !== '') {
                    $user->setPasswordHash($this->passwords->hash($data->password));
                }
            }

            $user->setActive($data->active);
            $this->assignGroups($user, $data->groups);
            $this->em->flush();

            $this->auditLogger->log($actorId, $isNew ? AuditAction::UserCreate : AuditAction::UserUpdate, 'user', $user->getId(), [
                'username' => $user->getUsername(),
                'groups' => $user->getGroupNames(),
                'passwordChanged' => !$isNew && $data->password !== '',
            ]);

            return $user;
        });
    }

    // $actorId = kdo změnu udělal (do historie změn)
    public function create(string $username, string $email, string $password, ?int $actorId = null): User
    {
        $user = new User($username, $email, $this->passwords->hash($password));
        $this->em->persist($user);
        $this->em->flush();

        $this->auditLogger->log($actorId, AuditAction::UserCreate, 'user', $user->getId(), ['username' => $username]);

        return $user;
    }

    public function update(User $user, ?int $actorId = null): void
    {
        $this->em->flush();
        $this->auditLogger->log($actorId, AuditAction::UserUpdate, 'user', $user->getId(), ['username' => $user->getUsername()]);
    }

    public function changePassword(User $user, string $password, ?int $actorId = null): void
    {
        $user->setPasswordHash($this->passwords->hash($password));
        $this->em->flush();

        $this->auditLogger->log($actorId, AuditAction::UserChangePassword, 'user', $user->getId());
    }

    /** Přepíše skupiny uživatele podle zadaných ID */
    public function setGroups(User $user, array $groupIds, ?int $actorId = null): void
    {
        $this->assignGroups($user, $groupIds);
        $this->em->flush();

        $this->auditLogger->log($actorId, AuditAction::UserSetGroups, 'user', $user->getId(), ['groups' => $user->getGroupNames()]);
    }

    public function delete(User $user, ?int $actorId = null): void
    {
        // po flush() Doctrine id z entity smaže, proto si ho uložíme předem
        $id = $user->getId();
        $username = $user->getUsername();

        $this->em->remove($user);
        $this->em->flush();

        $this->auditLogger->log($actorId, AuditAction::UserDelete, 'user', $id, ['username' => $username]);
    }

    private function assignGroups(User $user, array $groupIds): void
    {
        $user->clearGroups();

        // jedním dotazem místo find() pro každé ID zvlášť
        $groups = $groupIds ? $this->em->getRepository(Group::class)->findBy(['id' => $groupIds]) : [];
        foreach ($groups as $group) {
            $user->addGroup($group);
        }
    }

    private function isTaken(string $column, string $value, ?int $exceptId): bool
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where("u.$column = :value")
            ->setParameter('value', $value);

        if ($exceptId) {
            $qb->andWhere('u.id != :id')->setParameter('id', $exceptId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
