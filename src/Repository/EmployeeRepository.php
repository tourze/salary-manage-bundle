<?php

namespace Tourze\SalaryManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SalaryManageBundle\Contract\EmployeeRepositoryInterface;
use Tourze\SalaryManageBundle\Entity\Employee;

/**
 * Employee Repository - 只负责数据访问，无业务逻辑
 *
 * @extends ServiceEntityRepository<Employee>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: Employee::class)]
class EmployeeRepository extends ServiceEntityRepository implements EmployeeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    /** @return array<Employee> */
    public function findByDepartment(string $department): array
    {
        /** @var array<Employee> */
        return $this->createQueryBuilder('e')
            ->andWhere('e.department = :department')
            ->setParameter('department', $department)
            ->orderBy('e.employeeNumber', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByEmployeeNumber(string $employeeNumber): ?Employee
    {
        return $this->findOneBy(['employeeNumber' => $employeeNumber]);
    }

    /** @return array<Employee> */
    public function findActiveEmployees(): array
    {
        /** @var array<Employee> */
        return $this->createQueryBuilder('e')
            ->orderBy('e.employeeNumber', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<int, array<string, mixed>> */
    public function countByDepartment(?string $department = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e.department, COUNT(e.id) as count')
            ->groupBy('e.department')
        ;

        if (null !== $department) {
            $qb->andWhere('e.department = :department')
                ->setParameter('department', $department)
            ;
        }

        /** @var array<int, array<string, mixed>> */
        return $qb->getQuery()->getResult();
    }

    public function save(Employee $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Employee $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
