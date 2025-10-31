<?php

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Entity\SalaryItem;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;

/**
 * 薪资计算实体测试
 * @internal
 */
#[CoversClass(SalaryCalculation::class)]
final class SalaryCalculationTest extends AbstractEntityTestCase
{
    private Employee $employee;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('EMP001');
        $this->employee->setName('张三');
        $this->employee->setBaseSalary('10000.00');
        $this->employee->setHireDate(new \DateTimeImmutable('2024-01-01'));

        $this->period = new PayrollPeriod();
        $this->period->setYear(2025);
        $this->period->setMonth(1);
    }

    protected function createEntity(): object
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($this->employee);
        $calculation->setPeriod($this->period);

        return $calculation;
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        // SalaryCalculation has complex relationships and computed properties
        // Only test simple writable properties (updatedAt is the only one available)
        // Other properties are tested through dedicated test methods
        return [
            ['updatedAt', new \DateTimeImmutable('2025-01-15')],
        ];
    }

    public function testConstructor(): void
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($this->employee);
        $calculation->setPeriod($this->period);

        $this->assertEquals($this->employee, $calculation->getEmployee());
        $this->assertEquals($this->period, $calculation->getPeriod());
        $this->assertCount(0, $calculation->getItems());
        $this->assertEquals(0.0, $calculation->getGrossAmount());
        $this->assertEquals(0.0, $calculation->getNetAmount());
    }

    public function testAddItem(): void
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($this->employee);
        $calculation->setPeriod($this->period);
        $item = new SalaryItem();
        $item->setType(SalaryItemType::BasicSalary);
        $item->setAmount(10000.0);
        $item->setDescription('基本工资');

        $calculation->addItem($item);

        $this->assertCount(1, $calculation->getItems());
        $this->assertEquals($item, $calculation->getItems()[0]);
    }

    public function testRemoveItem(): void
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($this->employee);
        $calculation->setPeriod($this->period);
        $item1 = new SalaryItem();
        $item1->setType(SalaryItemType::BasicSalary);
        $item1->setAmount(10000.0);
        $item1->setDescription('基本工资');
        $item2 = new SalaryItem();
        $item2->setType(SalaryItemType::Allowance);
        $item2->setAmount(1000.0);
        $item2->setDescription('津贴');

        $calculation->addItem($item1);
        $calculation->addItem($item2);
        $this->assertCount(2, $calculation->getItems());

        $calculation->removeItem($item1);
        $this->assertCount(1, $calculation->getItems());

        $remainingItems = $calculation->getItems()->toArray(); // 转换为数组
        $this->assertEquals($item2, array_values($remainingItems)[0]);
    }

    public function testGetGrossAmount(): void
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($this->employee);
        $calculation->setPeriod($this->period);

        $basicSalary = new SalaryItem();
        $basicSalary->setType(SalaryItemType::BasicSalary);
        $basicSalary->setAmount(10000.0);
        $basicSalary->setDescription('基本工资');
        $allowance = new SalaryItem();
        $allowance->setType(SalaryItemType::Allowance);
        $allowance->setAmount(1000.0);
        $allowance->setDescription('津贴');
        $overtime = new SalaryItem();
        $overtime->setType(SalaryItemType::Overtime);
        $overtime->setAmount(500.0);
        $overtime->setDescription('加班费');

        $calculation->addItem($basicSalary);
        $calculation->addItem($allowance);
        $calculation->addItem($overtime);

        $this->assertEquals(11500.0, $calculation->getGrossAmount());
    }

    public function testGetNetAmount(): void
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($this->employee);
        $calculation->setPeriod($this->period);

        $basicSalary = new SalaryItem();
        $basicSalary->setType(SalaryItemType::BasicSalary);
        $basicSalary->setAmount(10000.0);
        $basicSalary->setDescription('基本工资');
        $deduction = new SalaryItem();
        $deduction->setType(SalaryItemType::SocialInsurance);
        $deduction->setAmount(-800.0);
        $deduction->setDescription('社保');
        $tax = new SalaryItem();
        $tax->setType(SalaryItemType::IncomeTax);
        $tax->setAmount(-290.0);
        $tax->setDescription('个税');

        $calculation->addItem($basicSalary);
        $calculation->addItem($deduction);
        $calculation->addItem($tax);

        $this->assertEquals(8910.0, $calculation->getNetAmount());
    }

    public function testGetItemsByType(): void
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($this->employee);
        $calculation->setPeriod($this->period);

        $basicSalary = new SalaryItem();
        $basicSalary->setType(SalaryItemType::BasicSalary);
        $basicSalary->setAmount(10000.0);
        $basicSalary->setDescription('基本工资');
        $allowance1 = new SalaryItem();
        $allowance1->setType(SalaryItemType::Allowance);
        $allowance1->setAmount(1000.0);
        $allowance1->setDescription('交通津贴');
        $allowance2 = new SalaryItem();
        $allowance2->setType(SalaryItemType::Allowance);
        $allowance2->setAmount(500.0);
        $allowance2->setDescription('餐饮津贴');

        $calculation->addItem($basicSalary);
        $calculation->addItem($allowance1);
        $calculation->addItem($allowance2);

        $allowances = $calculation->getItemsByType(SalaryItemType::Allowance);
        $this->assertCount(2, $allowances);

        $allowances = array_values($allowances); // 重新索引数组
        $this->assertEquals(1000.0, $allowances[0]->getAmount());
        $this->assertEquals(500.0, $allowances[1]->getAmount());
    }

    public function testSetAndGetContextValue(): void
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($this->employee);
        $calculation->setPeriod($this->period);

        $calculation->setContextValue('overtime_hours', 10);
        $calculation->setContextValue('performance_score', 85.5);

        $this->assertEquals(10, $calculation->getContextValue('overtime_hours'));
        $this->assertEquals(85.5, $calculation->getContextValue('performance_score'));
        $this->assertNull($calculation->getContextValue('non_existent'));
    }

    public function testGetTotalByItemType(): void
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($this->employee);
        $calculation->setPeriod($this->period);

        $allowance1 = new SalaryItem();
        $allowance1->setType(SalaryItemType::Allowance);
        $allowance1->setAmount(1000.0);
        $allowance1->setDescription('交通津贴');
        $allowance2 = new SalaryItem();
        $allowance2->setType(SalaryItemType::Allowance);
        $allowance2->setAmount(500.0);
        $allowance2->setDescription('餐饮津贴');
        $deduction = new SalaryItem();
        $deduction->setType(SalaryItemType::SocialInsurance);
        $deduction->setAmount(-800.0);
        $deduction->setDescription('社保');

        $calculation->addItem($allowance1);
        $calculation->addItem($allowance2);
        $calculation->addItem($deduction);

        $this->assertEquals(1500.0, $calculation->getTotalByItemType(SalaryItemType::Allowance));
        $this->assertEquals(-800.0, $calculation->getTotalByItemType(SalaryItemType::SocialInsurance));
        $this->assertEquals(0.0, $calculation->getTotalByItemType(SalaryItemType::Overtime));
    }
}
