<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;

/**
 * @extends ServiceEntityRepository<PayrollPeriod>
 */
#[AsRepository(entityClass: PayrollPeriod::class)]
class PayrollPeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PayrollPeriod::class);
    }

    public function save(PayrollPeriod $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PayrollPeriod $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找当前期间
     */
    public function findCurrent(): ?PayrollPeriod
    {
        $now = new \DateTimeImmutable();

        return $this->findOneBy([
            'year' => (int) $now->format('Y'),
            'month' => (int) $now->format('n'),
        ]);
    }

    /**
     * 查找指定年月的期间
     */
    public function findByYearMonth(int $year, int $month): ?PayrollPeriod
    {
        return $this->findOneBy([
            'year' => $year,
            'month' => $month,
        ]);
    }

    /**
     * 查找所有开放的期间
     *
     * @return PayrollPeriod[]
     */
    public function findOpen(): array
    {
        return $this->findBy(['isClosed' => false], ['year' => 'DESC', 'month' => 'DESC']);
    }

    /**
     * 查找所有已关闭的期间
     *
     * @return PayrollPeriod[]
     */
    public function findClosed(): array
    {
        return $this->findBy(['isClosed' => true], ['year' => 'DESC', 'month' => 'DESC']);
    }

    /**
     * 查找指定年份的所有期间
     *
     * @return PayrollPeriod[]
     */
    public function findByYear(int $year): array
    {
        return $this->findBy(['year' => $year], ['month' => 'ASC']);
    }
}
