<?php

declare(strict_types=1);

namespace Matodo\Auth\Presentation\Audit;

use Doctrine\ORM\EntityManagerInterface;
use Matodo\Auth\Entity\AuditLog;
use Matodo\Auth\Presentation\BaseAdminPresenter;
use Matodo\Auth\Security\RequiresPermission;

#[RequiresPermission('audit', 'view')]
final class AuditPresenter extends BaseAdminPresenter
{
    private const PerPage = 50;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    // parametr nesmí být $action - to je v Nette název akce presenteru (default) a filtr by nikdy nic nenašel
    public function renderDefault(int $page = 1, string $filter = ''): void
    {
        // načteme o jeden záznam víc, ať víme, jestli existuje další stránka
        $qb = $this->em->createQueryBuilder()
            ->select('a')
            ->from(AuditLog::class, 'a')
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * self::PerPage)
            ->setMaxResults(self::PerPage + 1);

        if ($filter !== '') {
            $qb->andWhere('a.action LIKE :filter')
                ->setParameter('filter', '%' . $filter . '%');
        }

        $entries = $qb->getQuery()->getResult();

        $this->template->entries = array_slice($entries, 0, self::PerPage);
        $this->template->hasNext = count($entries) > self::PerPage;
        $this->template->page = $page;
        $this->template->filter = $filter;
    }
}
