<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;
use Tourze\SalaryManageBundle\Exception\SalaryCalculationException;
use Tourze\SalaryManageBundle\Service\Rules\BasicSalaryRule;
use Tourze\SalaryManageBundle\Service\Rules\OvertimeRule;
use Tourze\SalaryManageBundle\Service\SalaryCalculatorService;

/**
 * 薪资计算器服务功能测试
 * 验收标准：测试完整的薪资计算流程
 * @internal
 */
#[CoversClass(SalaryCalculatorService::class)]
class SalaryCalculatorServiceTest extends TestCase
{
    private SalaryCalculatorService $calculator;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new SalaryCalculatorService();

        // 创建测试员工
        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('EMP001');
        $this->employee->setName('张三');
        $this->employee->setBaseSalary('10000.00');
        $this->employee->setHireDate(new \DateTimeImmutable('2024-01-01'));
    }

    public function testCalculate(): void
    {
        // 添加基本薪资规则
        $this->calculator->addRule(new BasicSalaryRule());

        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $result = $this->calculator->calculate($this->employee, $period);

        // 验证计算结果
        $this->assertInstanceOf(SalaryCalculation::class, $result);
        $this->assertEquals(10000.0, $result->getGrossAmount());
        $this->assertEquals(10000.0, $result->getNetAmount());
        $this->assertCount(1, $result->getItems());
    }

    public function testAddRule(): void
    {
        $basicRule = new BasicSalaryRule();
        $this->calculator->addRule($basicRule);

        $rules = $this->calculator->getRules();
        $this->assertCount(1, $rules);
        $this->assertEquals(SalaryItemType::BasicSalary->value, $rules[0]->getType());
    }

    public function testRemoveRule(): void
    {
        $basicRule = new BasicSalaryRule();
        $overtimeRule = new OvertimeRule();

        // 添加规则
        $this->calculator->addRule($basicRule);
        $this->calculator->addRule($overtimeRule);

        $this->assertCount(2, $this->calculator->getRules());

        // 移除规则
        $this->calculator->removeRule(SalaryItemType::Overtime->value);

        $rules = $this->calculator->getRules();
        $this->assertCount(1, $rules);
        $this->assertEquals(SalaryItemType::BasicSalary->value, $rules[0]->getType());
    }

    public function testBasicSalaryCalculation(): void
    {
        // 添加基本薪资规则
        $this->calculator->addRule(new BasicSalaryRule());

        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $result = $this->calculator->calculate($this->employee, $period);

        // 验证计算结果
        $this->assertEquals(10000.0, $result->getGrossAmount());
        $this->assertEquals(10000.0, $result->getNetAmount());
        $this->assertCount(1, $result->getItems());

        $items = $result->getItems();
        $basicSalaryItem = $items[0];
        $this->assertNotNull($basicSalaryItem);
        $this->assertEquals(SalaryItemType::BasicSalary->value, $basicSalaryItem->getType()->value);
        $this->assertEquals(10000.0, $basicSalaryItem->getAmount());
    }

    public function testOvertimeCalculation(): void
    {
        // 添加基本薪资和加班费规则
        $this->calculator->addRule(new BasicSalaryRule());
        $this->calculator->addRule(new OvertimeRule());

        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $result = $this->calculator->calculate($this->employee, $period);

        // 基本工资 + 加班费（但没有设置加班时间，所以加班费为0）
        $this->assertEquals(10000.0, $result->getGrossAmount());
        $this->assertCount(2, $result->getItems()); // 基本工资 + 加班费

        // 测试有加班时间的情况
        $result->setContextValue('overtime_hours', 10);
        $result2 = $this->calculator->calculate($this->employee, $period);

        // 应该仍然是原来的结果，因为context是在计算前设置的
        $this->assertEquals(10000.0, $result2->getGrossAmount());
    }

    public function testRuleManagement(): void
    {
        $basicRule = new BasicSalaryRule();
        $overtimeRule = new OvertimeRule();

        // 测试添加规则
        $this->calculator->addRule($basicRule);
        $this->calculator->addRule($overtimeRule);

        $rules = $this->calculator->getRules();
        $this->assertCount(2, $rules);

        // 测试移除规则
        $this->calculator->removeRule(SalaryItemType::Overtime->value);

        $rules = $this->calculator->getRules();
        $this->assertCount(1, $rules);
        $this->assertEquals(SalaryItemType::BasicSalary->value, $rules[0]->getType());
    }

    public function testRuleOrdering(): void
    {
        $basicRule = new BasicSalaryRule();
        $overtimeRule = new OvertimeRule();

        // 添加规则（顺序与执行优先级不同）
        $this->calculator->addRule($overtimeRule);
        $this->calculator->addRule($basicRule);

        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $result = $this->calculator->calculate($this->employee, $period);

        $items = $result->getItems();
        // 基本薪资规则order=10，应该先执行
        $firstItem = $items[0];
        $this->assertNotNull($firstItem);
        $this->assertEquals(SalaryItemType::BasicSalary->value, $firstItem->getType()->value);
        // 加班费规则order=20，应该后执行
        $secondItem = $items[1];
        $this->assertNotNull($secondItem);
        $this->assertEquals(SalaryItemType::Overtime->value, $secondItem->getType()->value);
    }

    public function testEmployeeWithZeroBaseSalary(): void
    {
        // 创建零基本工资员工
        $zeroSalaryEmployee = new Employee();
        $zeroSalaryEmployee->setEmployeeNumber('EMP002');
        $zeroSalaryEmployee->setName('李四');
        $zeroSalaryEmployee->setBaseSalary('0.00');
        $zeroSalaryEmployee->setHireDate(new \DateTimeImmutable('2024-01-01'));

        $this->calculator->addRule(new BasicSalaryRule());

        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);

        $this->expectException(SalaryCalculationException::class);
        $this->expectExceptionMessage('薪资计算结果不能为空');

        $this->calculator->calculate($zeroSalaryEmployee, $period);
    }
}
