<?php

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SalaryManageBundle\Entity\Employee;

/**
 * 员工实体测试
 * @internal
 */
#[CoversClass(Employee::class)]
final class EmployeeTest extends AbstractEntityTestCase
{
    /**
     * 创建被测实体的一个实例.
     */
    protected function createEntity(): object
    {
        $employee = new Employee();
        $employee->setEmployeeNumber('EMP001');
        $employee->setName('张三');
        $employee->setBaseSalary('10000.00');
        $employee->setHireDate(new \DateTimeImmutable('2024-01-01'));

        return $employee;
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'employeeNumber' => ['employeeNumber', 'EMP001'],
            'name' => ['name', '张三'],
            'department' => ['department', '技术部'],
            'baseSalary' => ['baseSalary', '10000.00'],
            'specialDeductions' => ['specialDeductions', ['child_education' => 1000.00]],
            'hireDate' => ['hireDate', new \DateTimeImmutable('2024-01-01')],
            'idNumber' => ['idNumber', '123456789012345678'],
        ];
    }
}
