<?php

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;

/**
 * 工资期间实体测试
 * @internal
 */
#[CoversClass(PayrollPeriod::class)]
final class PayrollPeriodTest extends AbstractEntityTestCase
{
    /**
     * 创建被测实体的一个实例.
     */
    protected function createEntity(): object
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(3);
        $period->setIsClosed(false);

        return $period;
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'year' => ['year', 2025],
            'month' => ['month', 3],
            // 排除 isClosed 属性，因为其方法命名不符合标准 (isIsClosed/setIsClosed)
            // 该属性会在原有的人工编写的测试方法中测试
        ];
    }

    public function testConstructor(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(3);

        $this->assertEquals(2025, $period->getYear());
        $this->assertEquals(3, $period->getMonth());
    }

    public function testGetDisplayName(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(3);

        $this->assertEquals('2025年3月', $period->getDisplayName());
    }

    public function testGetStartDate(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(3);

        $startDate = $period->getStartDate();

        $this->assertEquals('2025-03-01', $startDate->format('Y-m-d'));
    }

    public function testGetEndDate(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(3);

        $endDate = $period->getEndDate();

        $this->assertEquals('2025-03-31', $endDate->format('Y-m-d'));
    }

    public function testIsCurrent(): void
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');

        $currentPeriod = new PayrollPeriod();
        $currentPeriod->setYear($currentYear);
        $currentPeriod->setMonth($currentMonth);
        $this->assertTrue($currentPeriod->isCurrent());

        $pastPeriod = new PayrollPeriod();
        $pastPeriod->setYear(2020);
        $pastPeriod->setMonth(1);
        $this->assertFalse($pastPeriod->isCurrent());
    }

    public function testGetDaysInMonth(): void
    {
        $march2025 = new PayrollPeriod();
        $march2025->setYear(2025);
        $march2025->setMonth(3);
        $this->assertEquals(31, $march2025->getDaysInMonth());

        $february2025 = new PayrollPeriod();
        $february2025->setYear(2025);
        $february2025->setMonth(2);
        $this->assertEquals(28, $february2025->getDaysInMonth());

        // 闰年测试
        $february2024 = new PayrollPeriod();
        $february2024->setYear(2024);
        $february2024->setMonth(2);
        $this->assertEquals(29, $february2024->getDaysInMonth());
    }

    public function testGetNextPeriod(): void
    {
        $march2025 = new PayrollPeriod();
        $march2025->setYear(2025);
        $march2025->setMonth(3);
        $nextPeriod = $march2025->getNextPeriod();

        $this->assertEquals(2025, $nextPeriod->getYear());
        $this->assertEquals(4, $nextPeriod->getMonth());

        // 跨年测试
        $december2025 = new PayrollPeriod();
        $december2025->setYear(2025);
        $december2025->setMonth(12);
        $nextYear = $december2025->getNextPeriod();

        $this->assertEquals(2026, $nextYear->getYear());
        $this->assertEquals(1, $nextYear->getMonth());
    }

    public function testGetPreviousPeriod(): void
    {
        $march2025 = new PayrollPeriod();
        $march2025->setYear(2025);
        $march2025->setMonth(3);
        $previousPeriod = $march2025->getPreviousPeriod();

        $this->assertEquals(2025, $previousPeriod->getYear());
        $this->assertEquals(2, $previousPeriod->getMonth());

        // 跨年测试
        $january2025 = new PayrollPeriod();
        $january2025->setYear(2025);
        $january2025->setMonth(1);
        $previousYear = $january2025->getPreviousPeriod();

        $this->assertEquals(2024, $previousYear->getYear());
        $this->assertEquals(12, $previousYear->getMonth());
    }
}
