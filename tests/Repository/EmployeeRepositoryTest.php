<?php

namespace Tourze\SalaryManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Repository\EmployeeRepository;

/**
 * Employee Repository 测试
 * @internal
 */
#[CoversClass(EmployeeRepository::class)]
#[RunTestsInSeparateProcesses]
class EmployeeRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $employee = new Employee();
        $employee->setEmployeeNumber('TEST_' . uniqid());
        $employee->setName('测试员工');
        $employee->setBaseSalary('10000.00');
        $employee->setHireDate(new \DateTimeImmutable());

        return $employee;
    }

    protected function getRepository(): EmployeeRepository
    {
        $repository = self::getEntityManager()->getRepository(Employee::class);
        self::assertInstanceOf(EmployeeRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // 子类可以实现额外的设置逻辑
    }

    public function testSaveAndRemoveMethods(): void
    {
        $repository = $this->getRepository();

        $employee = new Employee();
        $employee->setEmployeeNumber('SAVE_TEST_' . uniqid());
        $employee->setName('李四');
        $employee->setBaseSalary('12000.00');
        $employee->setHireDate(new \DateTimeImmutable('2024-02-01'));

        $repository->save($employee, true);

        $this->assertNotNull($employee->getId());

        // 验证能够从数据库中找到保存的实体
        $foundEmployee = $repository->find($employee->getId());
        $this->assertNotNull($foundEmployee);
        $this->assertEquals($employee->getEmployeeNumber(), $foundEmployee->getEmployeeNumber());

        // 测试删除
        $id = $employee->getId();
        $repository->remove($employee, true);

        // 验证已被删除
        $deletedEmployee = $repository->find($id);
        $this->assertNull($deletedEmployee);
    }

    public function testCountByDepartment(): void
    {
        $repository = $this->getRepository();

        // 创建不同部门的员工
        $emp1 = new Employee();
        $emp1->setEmployeeNumber('DEPT_TEST_' . uniqid());
        $emp1->setName('赵六');
        $emp1->setDepartment('技术部');
        $emp1->setBaseSalary('16000.00');
        $emp1->setHireDate(new \DateTimeImmutable('2024-04-01'));

        $emp2 = new Employee();
        $emp2->setEmployeeNumber('DEPT_TEST_' . uniqid());
        $emp2->setName('刘七');
        $emp2->setDepartment('技术部');
        $emp2->setBaseSalary('18000.00');
        $emp2->setHireDate(new \DateTimeImmutable('2024-05-01'));

        $emp3 = new Employee();
        $emp3->setEmployeeNumber('DEPT_TEST_' . uniqid());
        $emp3->setName('陈八');
        $emp3->setDepartment('销售部');
        $emp3->setBaseSalary('14000.00');
        $emp3->setHireDate(new \DateTimeImmutable('2024-06-01'));

        $repository->save($emp1, true);
        $repository->save($emp2, true);
        $repository->save($emp3, true);

        // 测试按部门统计 - 这个方法返回统计数据的数组
        $departmentStats = $repository->countByDepartment();

        $this->assertIsArray($departmentStats);
        $this->assertGreaterThanOrEqual(2, count($departmentStats)); // 至少有技术部和销售部

        // 验证结果包含正确的部门统计信息
        $techCount = 0;
        $salesCount = 0;
        foreach ($departmentStats as $stat) {
            if ('技术部' === $stat['department']) {
                $techCount = is_numeric($stat['count']) ? (int) $stat['count'] : 0;
            }
            if ('销售部' === $stat['department']) {
                $salesCount = is_numeric($stat['count']) ? (int) $stat['count'] : 0;
            }
        }

        $this->assertGreaterThanOrEqual(2, $techCount); // 至少包含我们创建的2个
        $this->assertGreaterThanOrEqual(1, $salesCount); // 至少包含我们创建的1个
    }

    public function testFindByEmployeeNumber(): void
    {
        $repository = $this->getRepository();

        // 创建测试员工
        $employee = new Employee();
        $uniqueNumber = 'EMPNO_TEST_' . uniqid();
        $employee->setEmployeeNumber($uniqueNumber);
        $employee->setName('孙九');
        $employee->setBaseSalary('13000.00');
        $employee->setHireDate(new \DateTimeImmutable('2024-07-01'));

        $repository->save($employee, true);

        // 按员工编号查找
        $foundEmployee = $repository->findByEmployeeNumber($uniqueNumber);

        $this->assertNotNull($foundEmployee);
        $this->assertEquals('孙九', $foundEmployee->getName());
    }

    public function testFindByDepartment(): void
    {
        $repository = $this->getRepository();

        // 创建同部门员工
        $emp1 = new Employee();
        $emp1->setEmployeeNumber('DEPT2_TEST_' . uniqid());
        $emp1->setName('周十');
        $emp1->setDepartment('人事部');
        $emp1->setBaseSalary('11000.00');
        $emp1->setHireDate(new \DateTimeImmutable('2024-08-01'));

        $emp2 = new Employee();
        $emp2->setEmployeeNumber('DEPT2_TEST_' . uniqid());
        $emp2->setName('吴十一');
        $emp2->setDepartment('人事部');
        $emp2->setBaseSalary('12000.00');
        $emp2->setHireDate(new \DateTimeImmutable('2024-09-01'));

        $repository->save($emp1, true);
        $repository->save($emp2, true);

        // 按部门查找
        $hrEmployees = $repository->findByDepartment('人事部');

        $this->assertGreaterThanOrEqual(2, count($hrEmployees)); // 包含固件数据和我们创建的数据
    }

    public function testFindHighSalaryEmployees(): void
    {
        $repository = $this->getRepository();

        // 创建不同薪资的员工
        $lowSalaryEmp = new Employee();
        $lowSalaryEmp->setEmployeeNumber('SALARY_TEST_' . uniqid());
        $lowSalaryEmp->setName('郑十二');
        $lowSalaryEmp->setBaseSalary('8000.00');
        $lowSalaryEmp->setHireDate(new \DateTimeImmutable('2024-10-01'));

        $highSalaryEmp = new Employee();
        $highSalaryEmp->setEmployeeNumber('SALARY_TEST_' . uniqid());
        $highSalaryEmp->setName('王十三');
        $highSalaryEmp->setBaseSalary('25000.00');
        $highSalaryEmp->setHireDate(new \DateTimeImmutable('2024-11-01'));

        $repository->save($lowSalaryEmp, true);
        $repository->save($highSalaryEmp, true);

        // 查找高薪员工
        $allEmployees = $repository->findAll();
        $highSalaryEmployees = array_filter($allEmployees, fn (Employee $emp) => (float) $emp->getBaseSalary() >= 20000);

        $this->assertGreaterThanOrEqual(1, count($highSalaryEmployees));
    }

    public function testFindActiveEmployees(): void
    {
        $repository = $this->getRepository();

        // 创建员工
        $employee = new Employee();
        $employee->setEmployeeNumber('ACTIVE_TEST_' . uniqid());
        $employee->setName('孟十四');
        $employee->setBaseSalary('14000.00');
        $employee->setHireDate(new \DateTimeImmutable('2024-12-01'));

        $repository->save($employee, true);

        // 查找活跃员工
        $activeEmployees = $repository->findActiveEmployees();

        $this->assertIsArray($activeEmployees);
        $this->assertGreaterThanOrEqual(1, count($activeEmployees));
    }

    public function testRemove(): void
    {
        $repository = $this->getRepository();

        // 创建员工
        $employee = new Employee();
        $employee->setEmployeeNumber('REMOVE_TEST_' . uniqid());
        $employee->setName('程十五');
        $employee->setBaseSalary('13000.00');
        $employee->setHireDate(new \DateTimeImmutable('2025-01-01'));

        $repository->save($employee, true);
        $id = $employee->getId();

        // 删除员工
        $repository->remove($employee, true);

        // 验证已被删除
        $deletedEmployee = $repository->find($id);
        $this->assertNull($deletedEmployee);
    }
}
