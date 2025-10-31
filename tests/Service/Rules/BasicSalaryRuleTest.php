<?php

namespace Tourze\SalaryManageBundle\Tests\Service\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Entity\SalaryItem;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;
use Tourze\SalaryManageBundle\Service\Rules\BasicSalaryRule;

/**
 * 基本薪资规则测试
 * @internal
 */
#[CoversClass(BasicSalaryRule::class)]
class BasicSalaryRuleTest extends TestCase
{
    private BasicSalaryRule $rule;

    private Employee $employee;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new BasicSalaryRule();

        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('EMP001');
        $this->employee->setName('张三');
        $this->employee->setBaseSalary('10000.00');
        $this->employee->setHireDate(new \DateTimeImmutable('2024-01-01'));

        $this->period = new PayrollPeriod();
        $this->period->setYear(2025);
        $this->period->setMonth(1);
    }

    public function testGetType(): void
    {
        $this->assertEquals(SalaryItemType::BasicSalary->value, $this->rule->getType());
    }

    public function testGetName(): void
    {
        $this->assertEquals('基本薪资规则', $this->rule->getName());
    }

    public function testGetDescription(): void
    {
        $this->assertEquals('计算员工的基本工资', $this->rule->getDescription());
    }

    public function testGetOrder(): void
    {
        $this->assertEquals(10, $this->rule->getOrder());
    }

    public function testIsApplicable(): void
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($this->employee);
        $calculation->setPeriod($this->period);

        $this->assertTrue($this->rule->isApplicable($this->employee, $calculation, []));
    }

    public function testExecute(): void
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($this->employee);
        $calculation->setPeriod($this->period);

        $this->rule->execute($this->employee, $calculation, []);

        $items = $calculation->getItems();
        $this->assertCount(1, $items);

        $basicSalaryItem = $items[0];
        $this->assertNotNull($basicSalaryItem);
        $this->assertEquals(SalaryItemType::BasicSalary, $basicSalaryItem->getType());
        $this->assertEquals(10000.0, $basicSalaryItem->getAmount());
        $this->assertEquals('基本工资', $basicSalaryItem->getDescription());
    }

    public function testExecuteWithZeroBaseSalary(): void
    {
        $zeroSalaryEmployee = new Employee();
        $zeroSalaryEmployee->setEmployeeNumber('EMP002');
        $zeroSalaryEmployee->setName('李四');
        $zeroSalaryEmployee->setBaseSalary('0.00');
        $zeroSalaryEmployee->setHireDate(new \DateTimeImmutable('2024-01-01'));

        $calculation = new SalaryCalculation();
        $calculation->setEmployee($zeroSalaryEmployee);
        $calculation->setPeriod($this->period);

        $this->rule->execute($zeroSalaryEmployee, $calculation, []);

        $items = $calculation->getItems();
        $this->assertCount(1, $items);

        $basicSalaryItem = $items[0];
        $this->assertNotNull($basicSalaryItem);
        $this->assertEquals(0.0, $basicSalaryItem->getAmount());
    }

    public function testExecuteWithCustomAmount(): void
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($this->employee);
        $calculation->setPeriod($this->period);
        $context = ['base_salary_override' => 12000.0];

        $this->rule->execute($this->employee, $calculation, $context);

        $items = $calculation->getItems();
        $this->assertCount(1, $items);

        $basicSalaryItem = $items[0];
        $this->assertNotNull($basicSalaryItem);
        $this->assertEquals(12000.0, $basicSalaryItem->getAmount());
    }

    public function testExecuteWithPartialMonth(): void
    {
        // 测试入职不满一个月的情况
        $newEmployee = new Employee();
        $newEmployee->setEmployeeNumber('EMP003');
        $newEmployee->setName('王五');
        $newEmployee->setBaseSalary('10000.00');
        $newEmployee->setHireDate(new \DateTimeImmutable('2025-01-15')); // 1月15日入职

        $calculation = new SalaryCalculation();
        $calculation->setEmployee($newEmployee);
        $calculation->setPeriod($this->period);
        $context = ['worked_days' => 17]; // 1月有31天，工作17天

        $this->rule->execute($newEmployee, $calculation, $context);

        $items = $calculation->getItems();
        $this->assertCount(1, $items);

        $basicSalaryItem = $items[0];
        $this->assertNotNull($basicSalaryItem);
        // 预期薪资 = 10000 * (17/31) ≈ 5483.87
        $this->assertEqualsWithDelta(5483.87, $basicSalaryItem->getAmount(), 0.01);
    }

    public function testCalculate(): void
    {
        $result = $this->rule->calculate($this->employee, $this->period);

        $this->assertInstanceOf(SalaryItem::class, $result);
        $this->assertEquals(SalaryItemType::BasicSalary, $result->getType());
        $this->assertEquals(10000.0, $result->getAmount());
        $this->assertEquals('基本工资', $result->getDescription());
    }

    public function testCalculateWithContext(): void
    {
        $context = ['base_salary_override' => 15000.0];
        $result = $this->rule->calculate($this->employee, $this->period, $context);

        $this->assertEquals(15000.0, $result->getAmount());
    }

    public function testCalculateWithZeroSalary(): void
    {
        $zeroSalaryEmployee = new Employee();
        $zeroSalaryEmployee->setEmployeeNumber('EMP002');
        $zeroSalaryEmployee->setName('李四');
        $zeroSalaryEmployee->setBaseSalary('0.00');
        $zeroSalaryEmployee->setHireDate(new \DateTimeImmutable('2024-01-01'));

        $result = $this->rule->calculate($zeroSalaryEmployee, $this->period);

        $this->assertEquals(0.0, $result->getAmount());
    }
}
