<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;

/**
 * @extends ServiceEntityRepository<SalaryCalculation>
 */
#[AsRepository(entityClass: SalaryCalculation::class)]
final class SalaryCalculationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SalaryCalculation::class);
    }

    public function save(SalaryCalculation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SalaryCalculation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据员工和薪资期间查找工资计算
     *
     * @return SalaryCalculation|null
     */
    public function findByEmployeeAndPeriod(Employee $employee, PayrollPeriod $period): ?SalaryCalculation
    {
        /** @var SalaryCalculation|null */
        return $this->createQueryBuilder('sc')
            ->andWhere('sc.employee = :employee')
            ->andWhere('sc.period = :period')
            ->setParameter('employee', $employee)
            ->setParameter('period', $period)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 根据薪资期间查找所有工资计算
     *
     * @return array<SalaryCalculation>
     */
    public function findByPeriod(PayrollPeriod $period): array
    {
        /** @var array<SalaryCalculation> */
        return $this->createQueryBuilder('sc')
            ->andWhere('sc.period = :period')
            ->setParameter('period', $period)
            ->orderBy('sc.employee')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据员工查找所有工资计算（按期间倒序）
     *
     * @return array<SalaryCalculation>
     */
    public function findByEmployee(Employee $employee): array
    {
        /** @var array<SalaryCalculation> */
        return $this->createQueryBuilder('sc')
            ->andWhere('sc.employee = :employee')
            ->setParameter('employee', $employee)
            ->orderBy('sc.period', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
