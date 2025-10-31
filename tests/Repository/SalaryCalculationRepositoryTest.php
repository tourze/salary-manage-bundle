<?php

namespace Tourze\SalaryManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Repository\SalaryCalculationRepository;

/**
 * SalaryCalculation Repository 测试
 * @internal
 */
#[CoversClass(SalaryCalculationRepository::class)]
#[RunTestsInSeparateProcesses]
class SalaryCalculationRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $em = self::getEntityManager();

        // 创建测试员工
        $employee = new Employee();
        $employee->setEmployeeNumber('TEST_SC_' . uniqid());
        $employee->setName('测试员工');
        $employee->setBaseSalary('10000.00');
        $employee->setHireDate(new \DateTimeImmutable());
        $em->persist($employee);

        // 创建测试薪资期间
        $period = new PayrollPeriod();
        $period->setYear((int) date('Y'));
        $period->setMonth((int) date('n'));
        $em->persist($period);

        $em->flush();

        // 创建薪资计算
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($employee);
        $calculation->setPeriod($period);

        return $calculation;
    }

    protected function getRepository(): SalaryCalculationRepository
    {
        $repository = self::getEntityManager()->getRepository(SalaryCalculation::class);
        self::assertInstanceOf(SalaryCalculationRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // 子类可以实现额外的设置逻辑
    }

    public function testSaveAndRemoveMethods(): void
    {
        $repository = $this->getRepository();
        $calculation = $this->createNewEntity();
        $this->assertInstanceOf(SalaryCalculation::class, $calculation);

        $repository->save($calculation, true);

        $this->assertNotNull($calculation->getId());

        // 验证能够从数据库中找到保存的实体
        $foundCalculation = $repository->find($calculation->getId());
        $this->assertNotNull($foundCalculation);

        // 测试删除
        $id = $calculation->getId();
        $repository->remove($calculation, true);

        // 验证已被删除
        $deletedCalculation = $repository->find($id);
        $this->assertNull($deletedCalculation);
    }

    public function testFindByEmployeeAndPeriod(): void
    {
        $repository = $this->getRepository();
        $calculation = $this->createNewEntity();
        $this->assertInstanceOf(SalaryCalculation::class, $calculation);
        $repository->save($calculation, true);

        $employee = $calculation->getEmployee();
        $period = $calculation->getPeriod();

        // 按员工和期间查找
        $foundCalculation = $repository->findByEmployeeAndPeriod($employee, $period);

        $this->assertNotNull($foundCalculation);
        $this->assertEquals($calculation->getId(), $foundCalculation->getId());
    }

    public function testFindByPeriod(): void
    {
        $repository = $this->getRepository();
        $calculation = $this->createNewEntity();
        $this->assertInstanceOf(SalaryCalculation::class, $calculation);
        $repository->save($calculation, true);

        $period = $calculation->getPeriod();

        // 按期间查找所有计算
        $calculations = $repository->findByPeriod($period);

        $this->assertIsArray($calculations);
        $this->assertGreaterThanOrEqual(1, count($calculations));

        // 验证返回的是正确类型
        foreach ($calculations as $calc) {
            $this->assertInstanceOf(SalaryCalculation::class, $calc);
        }
    }

    public function testFindByEmployee(): void
    {
        $repository = $this->getRepository();
        $calculation = $this->createNewEntity();
        $this->assertInstanceOf(SalaryCalculation::class, $calculation);
        $repository->save($calculation, true);

        $employee = $calculation->getEmployee();

        // 按员工查找所有计算
        $calculations = $repository->findByEmployee($employee);

        $this->assertIsArray($calculations);
        $this->assertGreaterThanOrEqual(1, count($calculations));

        // 验证返回的是正确类型
        foreach ($calculations as $calc) {
            $this->assertInstanceOf(SalaryCalculation::class, $calc);
            $this->assertEquals($employee->getId(), $calc->getEmployee()->getId());
        }
    }
}