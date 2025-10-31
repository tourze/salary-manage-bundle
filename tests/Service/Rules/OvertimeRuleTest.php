<?php

namespace Tourze\SalaryManageBundle\Tests\Service\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryItem;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;
use Tourze\SalaryManageBundle\Service\Rules\OvertimeRule;

/**
 * @internal
 */
#[CoversClass(OvertimeRule::class)]
class OvertimeRuleTest extends TestCase
{
    private OvertimeRule $rule;

    private Employee $employee;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new OvertimeRule();

        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('EMP001');
        $this->employee->setBaseSalary('17400.00'); // 174小时 * 100元/小时

        $this->period = new PayrollPeriod();
        $this->period->setYear(2025);
        $this->period->setMonth(1);
    }

    public function testGetType(): void
    {
        $this->assertEquals(SalaryItemType::Overtime->value, $this->rule->getType());
    }

    public function testGetOrder(): void
    {
        $this->assertEquals(20, $this->rule->getOrder());
    }

    public function testCalculateWithBasicOvertime(): void
    {
        $context = [
            'overtime_hours' => 10.0,
            // 使用默认倍数 1.5
        ];

        $salaryItem = $this->rule->calculate($this->employee, $this->period, $context);

        $expectedHourlyRate = 17400.0 / 174; // 100元/小时
        $expectedAmount = 10.0 * $expectedHourlyRate * 1.5; // 1500元

        $this->assertEquals(SalaryItemType::Overtime, $salaryItem->getType());
        $this->assertEquals($expectedAmount, $salaryItem->getAmount());
        $this->assertEquals('加班费 (10.0小时 × 1.5倍)', $salaryItem->getDescription());

        $metadata = $salaryItem->getMetadata();
        $this->assertEquals(10.0, $metadata['overtime_hours']);
        $this->assertEquals($expectedHourlyRate, $metadata['hourly_rate']);
        $this->assertEquals(1.5, $metadata['overtime_multiplier']);
        $this->assertEquals('2025-01', $metadata['period']);
    }

    public function testCalculateWithCustomMultiplier(): void
    {
        $context = [
            'overtime_hours' => 8.0,
            'overtime_multiplier' => 2.0, // 双倍工资
        ];

        $salaryItem = $this->rule->calculate($this->employee, $this->period, $context);

        $expectedHourlyRate = 17400.0 / 174; // 100元/小时
        $expectedAmount = 8.0 * $expectedHourlyRate * 2.0; // 1600元

        $this->assertEquals($expectedAmount, $salaryItem->getAmount());
        $this->assertEquals('加班费 (8.0小时 × 2.0倍)', $salaryItem->getDescription());

        $metadata = $salaryItem->getMetadata();
        $this->assertEquals(2.0, $metadata['overtime_multiplier']);
    }

    public function testCalculateWithZeroOvertime(): void
    {
        $context = [
            'overtime_hours' => 0.0,
        ];

        $salaryItem = $this->rule->calculate($this->employee, $this->period, $context);

        $this->assertEquals(0.0, $salaryItem->getAmount());
        $this->assertEquals('加班费 (0.0小时 × 1.5倍)', $salaryItem->getDescription());
    }

    public function testCalculateWithFloatingPointHours(): void
    {
        $context = [
            'overtime_hours' => 2.5, // 2.5小时
            'overtime_multiplier' => 1.5,
        ];

        $salaryItem = $this->rule->calculate($this->employee, $this->period, $context);

        $expectedHourlyRate = 17400.0 / 174; // 100元/小时
        $expectedAmount = 2.5 * $expectedHourlyRate * 1.5; // 375元

        $this->assertEquals($expectedAmount, $salaryItem->getAmount());
        $this->assertEquals('加班费 (2.5小时 × 1.5倍)', $salaryItem->getDescription());
    }

    public function testCalculateWithNoContextProvided(): void
    {
        $salaryItem = $this->rule->calculate($this->employee, $this->period, []);

        // 默认值：0小时加班，1.5倍
        $this->assertEquals(0.0, $salaryItem->getAmount());
        $this->assertEquals('加班费 (0.0小时 × 1.5倍)', $salaryItem->getDescription());

        $metadata = $salaryItem->getMetadata();
        $this->assertEquals(0, $metadata['overtime_hours']);
        $this->assertEquals(1.5, $metadata['overtime_multiplier']);
    }

    #[TestWith([5220.0, 10.0, 450.0])]
    #[TestWith([8700.0, 8.0, 600.0])]
    #[TestWith([26100.0, 5.0, 1125.0])]
    public function testCalculateWithDifferentBaseSalaries(
        float $baseSalary,
        float $overtimeHours,
        float $expectedAmount,
    ): void {
        $employee = new Employee();
        $employee->setBaseSalary((string) $baseSalary);

        $context = [
            'overtime_hours' => $overtimeHours,
            'overtime_multiplier' => 1.5,
        ];

        $salaryItem = $this->rule->calculate($employee, $this->period, $context);

        $this->assertEquals($expectedAmount, $salaryItem->getAmount());
    }

    public function testIsApplicableWithPositiveBaseSalary(): void
    {
        $employee = new Employee();
        $employee->setBaseSalary('10000.00');

        $this->assertTrue($this->rule->isApplicable($employee));
    }

    public function testIsApplicableWithZeroBaseSalary(): void
    {
        $employee = new Employee();
        $employee->setBaseSalary('0.00');

        $this->assertFalse($this->rule->isApplicable($employee));
    }

    public function testIsApplicableWithNegativeBaseSalary(): void
    {
        $employee = new Employee();
        $employee->setBaseSalary('-1000.00');

        $this->assertFalse($this->rule->isApplicable($employee));
    }

    public function testHourlyRateCalculation(): void
    {
        // 测试小时工资计算
        $testCases = [
            ['baseSalary' => 17400.0, 'expectedHourlyRate' => 100.0],
            ['baseSalary' => 8700.0, 'expectedHourlyRate' => 50.0],
            ['baseSalary' => 34800.0, 'expectedHourlyRate' => 200.0],
        ];

        foreach ($testCases as $testCase) {
            $employee = new Employee();
            $employee->setBaseSalary((string) $testCase['baseSalary']);

            $context = [
                'overtime_hours' => 1.0, // 1小时便于计算
                'overtime_multiplier' => 1.0, // 1倍便于计算
            ];

            $salaryItem = $this->rule->calculate($employee, $this->period, $context);
            $metadata = $salaryItem->getMetadata();

            $this->assertEquals($testCase['expectedHourlyRate'], $metadata['hourly_rate']);
            $this->assertEquals($testCase['expectedHourlyRate'], $salaryItem->getAmount());
        }
    }

    public function testComplexOvertimeScenario(): void
    {
        // 复杂场景：不同类型的加班
        $contexts = [
            [
                'overtime_hours' => 5.0,
                'overtime_multiplier' => 1.5, // 工作日加班
            ],
            [
                'overtime_hours' => 8.0,
                'overtime_multiplier' => 2.0, // 周末加班
            ],
            [
                'overtime_hours' => 4.0,
                'overtime_multiplier' => 3.0, // 法定节假日加班
            ],
        ];

        $expectedAmounts = [
            5.0 * 100.0 * 1.5, // 750.0
            8.0 * 100.0 * 2.0, // 1600.0
            4.0 * 100.0 * 3.0, // 1200.0
        ];

        foreach ($contexts as $index => $context) {
            $salaryItem = $this->rule->calculate($this->employee, $this->period, $context);
            $this->assertEquals($expectedAmounts[$index], $salaryItem->getAmount());
        }
    }

    public function testDescriptionFormatting(): void
    {
        $testCases = [
            ['hours' => 1.0, 'multiplier' => 1.5, 'expected' => '加班费 (1.0小时 × 1.5倍)'],
            ['hours' => 12.5, 'multiplier' => 2.0, 'expected' => '加班费 (12.5小时 × 2.0倍)'],
            ['hours' => 0.5, 'multiplier' => 3.0, 'expected' => '加班费 (0.5小时 × 3.0倍)'],
            ['hours' => 20.0, 'multiplier' => 1.0, 'expected' => '加班费 (20.0小时 × 1.0倍)'],
        ];

        foreach ($testCases as $testCase) {
            $context = [
                'overtime_hours' => $testCase['hours'],
                'overtime_multiplier' => $testCase['multiplier'],
            ];

            $salaryItem = $this->rule->calculate($this->employee, $this->period, $context);
            $this->assertEquals($testCase['expected'], $salaryItem->getDescription());
        }
    }

    public function testMetadataCompleteness(): void
    {
        $context = [
            'overtime_hours' => 15.0,
            'overtime_multiplier' => 2.5,
        ];

        $salaryItem = $this->rule->calculate($this->employee, $this->period, $context);
        $metadata = $salaryItem->getMetadata();

        // 验证所有必要的元数据都存在
        $this->assertArrayHasKey('overtime_hours', $metadata);
        $this->assertArrayHasKey('hourly_rate', $metadata);
        $this->assertArrayHasKey('overtime_multiplier', $metadata);
        $this->assertArrayHasKey('period', $metadata);

        $this->assertEquals(15.0, $metadata['overtime_hours']);
        $this->assertEquals(100.0, $metadata['hourly_rate']); // 17400 / 174
        $this->assertEquals(2.5, $metadata['overtime_multiplier']);
        $this->assertEquals('2025-01', $metadata['period']);
    }

    public function testCalculationRuleInterface(): void
    {
        // 验证实现了 CalculationRuleInterface 的所有方法
        $this->assertIsString($this->rule->getType());
        $this->assertIsInt($this->rule->getOrder());
        $this->assertIsBool($this->rule->isApplicable($this->employee));

        // 计算方法应该返回 SalaryItem
        $salaryItem = $this->rule->calculate($this->employee, $this->period, []);
        $this->assertInstanceOf(SalaryItem::class, $salaryItem);
    }

    public function testMonthlyWorkingHoursConstant(): void
    {
        // 验证月工作时间计算：21.75天 × 8小时 = 174小时
        $employee = new Employee();
        $employee->setBaseSalary('17400.00'); // 设置为174的整数倍以便验证

        $context = [
            'overtime_hours' => 1.0,
            'overtime_multiplier' => 1.0,
        ];

        $salaryItem = $this->rule->calculate($employee, $this->period, $context);
        $metadata = $salaryItem->getMetadata();

        // 小时工资应该是 17400 / 174 = 100
        $this->assertEquals(100.0, $metadata['hourly_rate']);
        $this->assertEquals(100.0, $salaryItem->getAmount());
    }

    public function testEdgeCases(): void
    {
        // 测试边界情况
        $edgeCases = [
            'very_small_overtime' => ['hours' => 0.01, 'multiplier' => 1.5],
            'very_large_overtime' => ['hours' => 100.0, 'multiplier' => 1.5],
            'fractional_multiplier' => ['hours' => 8.0, 'multiplier' => 1.25],
            'zero_multiplier' => ['hours' => 8.0, 'multiplier' => 0.0],
        ];

        foreach ($edgeCases as $caseName => $case) {
            $context = [
                'overtime_hours' => $case['hours'],
                'overtime_multiplier' => $case['multiplier'],
            ];

            $salaryItem = $this->rule->calculate($this->employee, $this->period, $context);

            $expectedAmount = $case['hours'] * 100.0 * $case['multiplier'];
            $this->assertEquals($expectedAmount, $salaryItem->getAmount(), "Failed for case: {$caseName}");
        }
    }
}
