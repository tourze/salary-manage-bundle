<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Tests\Helper;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Contract\EmployeeRepositoryInterface;

/**
 * Mock实现用于测试EmployeeRepository
 */
class MockEmployeeRepository implements EmployeeRepositoryInterface
{
    /** @var array<Employee> */
    private array $activeEmployees = [];

    /** @var array<string, array<Employee>> */
    private array $departmentEmployees = [];

    /** @var array<string, Employee> */
    private array $employeesByNumber = [];

    /** @var array<string, int> */
    private array $callCounts = [];

    /** @var array<string, int> */
    private array $expectedCalls = [];

    public function __construct()
    {
        // 无依赖构造函数
    }

    /** @param array<Employee> $employees */
    public function setActiveEmployees(array $employees): void
    {
        $this->activeEmployees = $employees;
    }

    /** @param array<Employee> $employees */
    public function setDepartmentEmployees(string $department, array $employees): void
    {
        $this->departmentEmployees[$department] = $employees;
    }

    /** @param array<Employee> $employees */
    public function setEmployeesByNumber(array $employees): void
    {
        $this->employeesByNumber = [];
        foreach ($employees as $employee) {
            $this->employeesByNumber[$employee->getEmployeeNumber()] = $employee;
        }
    }

    public function expectCall(string $method, int $times = 1): void
    {
        $this->expectedCalls[$method] = $times;
        $this->callCounts[$method] = 0;
    }

    /** @return array<Employee> */
    public function findActiveEmployees(): array
    {
        $this->callCounts['findActiveEmployees'] = ($this->callCounts['findActiveEmployees'] ?? 0) + 1;

        return $this->activeEmployees;
    }

    /** @return array<Employee> */
    public function findByDepartment(string $department): array
    {
        $this->callCounts['findByDepartment'] = ($this->callCounts['findByDepartment'] ?? 0) + 1;

        return $this->departmentEmployees[$department] ?? [];
    }

    public function findByEmployeeNumber(string $employeeNumber): ?Employee
    {
        $this->callCounts['findByEmployeeNumber'] = ($this->callCounts['findByEmployeeNumber'] ?? 0) + 1;

        return $this->employeesByNumber[$employeeNumber] ?? null;
    }

    /** @return array<int, array<string, mixed>> */
    public function countByDepartment(?string $department = null): array
    {
        $this->callCounts['countByDepartment'] = ($this->callCounts['countByDepartment'] ?? 0) + 1;

        if ($department !== null) {
            return [
                ['department' => $department, 'count' => count($this->departmentEmployees[$department] ?? [])]
            ];
        }

        $result = [];
        foreach ($this->departmentEmployees as $dept => $employees) {
            $result[] = ['department' => $dept, 'count' => count($employees)];
        }
        return $result;
    }

    public function save(Employee $entity, bool $flush = true): void
    {
        $this->callCounts['save'] = ($this->callCounts['save'] ?? 0) + 1;
        // Mock implementation - no actual persistence
    }

    public function remove(Employee $entity, bool $flush = true): void
    {
        $this->callCounts['remove'] = ($this->callCounts['remove'] ?? 0) + 1;
        // Mock implementation - no actual persistence
    }

    public function verifyExpectedCalls(): void
    {
        foreach ($this->expectedCalls as $method => $expectedCount) {
            $actualCount = $this->callCounts[$method] ?? 0;
            if ($actualCount !== $expectedCount) {
                throw new \RuntimeException("Expected {$method} to be called {$expectedCount} times, but was called {$actualCount} times");
            }
        }
    }
}
