<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Contract;

use Tourze\SalaryManageBundle\Entity\Employee;

/**
 * EmployeeRepository 契约
 */
interface EmployeeRepositoryInterface
{
    /** @return array<Employee> */
    public function findByDepartment(string $department): array;

    public function findByEmployeeNumber(string $employeeNumber): ?Employee;

    /** @return array<Employee> */
    public function findActiveEmployees(): array;

    /** @return array<int, array<string, mixed>> */
    public function countByDepartment(?string $department = null): array;

    public function save(Employee $entity, bool $flush = true): void;

    public function remove(Employee $entity, bool $flush = true): void;
}