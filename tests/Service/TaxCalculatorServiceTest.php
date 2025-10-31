<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\Deduction;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\TaxResult;
use Tourze\SalaryManageBundle\Enum\DeductionType;
use Tourze\SalaryManageBundle\Exception\TaxCalculationException;
use Tourze\SalaryManageBundle\Service\TaxBracketProvider;
use Tourze\SalaryManageBundle\Service\TaxCalculatorService;

/**
 * 税务计算服务测试 - 测试累计预扣法实现
 * @internal
 */
#[CoversClass(TaxCalculatorService::class)]
class TaxCalculatorServiceTest extends TestCase
{
    private TaxCalculatorService $calculator;

    private Employee $employee;

    protected function setUp(): void
    {
        $this->calculator = new TaxCalculatorService(new TaxBracketProvider());

        // 创建测试员工
        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('EMP001');
        $this->employee->setName('张三');
        $this->employee->setBaseSalary('10000.00');
        $this->employee->setHireDate(new \DateTimeImmutable('2024-01-01'));
    }

    public function testCalculate(): void
    {
        // 测试基本的税务计算功能
        $taxableIncome = 8000;
        $context = [
            'current_period' => 1,
            'cumulative_income' => 8000,
            'cumulative_tax_paid' => 0,
            'period' => (function() { $period = new PayrollPeriod(); $period->setYear(2025); $period->setMonth(1); return $period; })(),
        ];

        $result = $this->calculator->calculate($this->employee, $taxableIncome, $context);

        $this->assertInstanceOf(TaxResult::class, $result);
        $this->assertEquals(90, $result->getTaxAmount());
        $this->assertEquals(7910, $result->getNetIncome());
        $this->assertEquals(0.03, $result->getMarginalTaxRate());
    }

    public function testSimpleTaxCalculationFirstMonth(): void
    {
        // 测试第一个月薪资8000元的个税计算
        $taxableIncome = 8000;
        $context = [
            'current_period' => 1,
            'cumulative_income' => 8000,
            'cumulative_tax_paid' => 0,
            'period' => (function() { $period = new PayrollPeriod(); $period->setYear(2025); $period->setMonth(1); return $period; })(),
        ];

        $result = $this->calculator->calculate($this->employee, $taxableIncome, $context);

        // 8000 - 5000(免征额) = 3000，税率3%，个税 = 3000 * 0.03 = 90
        $this->assertEquals(90, $result->getTaxAmount());
        $this->assertEquals(7910, $result->getNetIncome()); // 8000 - 90
        $this->assertEquals(0.03, $result->getMarginalTaxRate());
    }

    public function testTaxCalculationWithDeductions(): void
    {
        // 测试有专项附加扣除的情况
        $taxableIncome = 12000;
        $deductions = [
            new Deduction(DeductionType::ChildEducation, 2000, '子女教育'),
            new Deduction(DeductionType::HousingLoan, 1000, '住房贷款'),
        ];

        $context = [
            'current_period' => 1,
            'cumulative_income' => 12000,
            'cumulative_tax_paid' => 0,
            'deductions' => $deductions,
            'period' => (function() { $period = new PayrollPeriod(); $period->setYear(2025); $period->setMonth(1); return $period; })(),
        ];

        $result = $this->calculator->calculate($this->employee, $taxableIncome, $context);

        // 12000 - 5000(免征额) - 3000(专项附加扣除) = 4000
        // 税率3%，个税 = 4000 * 0.03 = 120
        $this->assertEquals(120, $result->getTaxAmount());
        $this->assertEquals(11880, $result->getNetIncome());
        $this->assertEquals(3000, $result->getTotalDeductions());
    }

    public function testCumulativeWithholdingMethod(): void
    {
        // 测试累计预扣法 - 第二个月
        $taxableIncome = 15000; // 当月收入
        $context = [
            'current_period' => 2,
            'cumulative_income' => 23000, // 1月8000 + 2月15000
            'cumulative_tax_paid' => 90,  // 1月已缴税90元
            'period' => (function() { $period = new PayrollPeriod(); $period->setYear(2025); $period->setMonth(2); return $period; })(),
        ];

        $result = $this->calculator->calculate($this->employee, $taxableIncome, $context);

        // 累计应税收入: 23000 - 10000(2个月免征额) = 13000
        // 累计应纳税额: 13000 * 0.03 = 390
        // 本月应缴: 390 - 90 = 300
        $this->assertEquals(300, $result->getTaxAmount());
        $this->assertEquals(14700, $result->getNetIncome());
    }

    public function testHighIncomeCalculation(): void
    {
        // 测试高收入的税率计算（年收入40万，适用25%税率）
        $annualIncome = 400000;
        $monthlyIncome = $annualIncome / 12;

        $context = [
            'current_period' => 12,
            'cumulative_income' => $annualIncome,
            'cumulative_tax_paid' => 48000, // 假设前11个月已缴税48000元
            'period' => (function() { $period = new PayrollPeriod(); $period->setYear(2025); $period->setMonth(12); return $period; })(),
        ];

        $result = $this->calculator->calculate($this->employee, $monthlyIncome, $context);

        // 累计应税收入: 400000 - 60000 = 340000
        // 适用第4档：340000 * 0.25 - 31920 = 53080（年度总税额）
        // 当月应缴：53080 - 48000 = 5080元
        $this->assertEquals(0.25, $result->getMarginalTaxRate());

        // 验证税后收入是正数
        $this->assertGreaterThan(0, $result->getNetIncome());

        // 验证当月应缴税额合理
        $this->assertGreaterThanOrEqual(0, $result->getTaxAmount());
    }

    public function testZeroIncomeCalculation(): void
    {
        // 测试零收入情况
        $context = [
            'current_period' => 1,
            'cumulative_income' => 0,
            'cumulative_tax_paid' => 0,
        ];

        $result = $this->calculator->calculate($this->employee, 0, $context);

        $this->assertEquals(0, $result->getTaxAmount());
        $this->assertEquals(0, $result->getNetIncome());
    }

    public function testBelowThresholdIncome(): void
    {
        // 测试低于免征额的收入
        $taxableIncome = 3000;
        $context = [
            'current_period' => 1,
            'cumulative_income' => 3000,
            'cumulative_tax_paid' => 0,
        ];

        $result = $this->calculator->calculate($this->employee, $taxableIncome, $context);

        // 3000 < 5000，无需缴税
        $this->assertEquals(0, $result->getTaxAmount());
        $this->assertEquals(3000, $result->getNetIncome());
    }

    public function testGetTaxBrackets(): void
    {
        $brackets = $this->calculator->getTaxBrackets();

        $this->assertCount(7, $brackets);
        $this->assertEquals(0.03, $brackets[0]->getRate());
        $this->assertEquals(0.45, $brackets[6]->getRate());
    }

    public function testValidateComplianceRules(): void
    {
        $taxableIncome = 10000;
        $result = $this->calculator->calculate($this->employee, $taxableIncome);

        $this->assertTrue($this->calculator->validateComplianceRules($result));
    }

    public function testNegativeIncomeThrowsException(): void
    {
        $this->expectException(TaxCalculationException::class);
        $this->expectExceptionMessage('应税收入不能为负数');

        $this->calculator->calculate($this->employee, -1000);
    }

    public function testInvalidPeriodThrowsException(): void
    {
        $context = ['current_period' => 15];

        $this->expectException(TaxCalculationException::class);
        $this->expectExceptionMessage('当前期数必须在1-12之间');

        $this->calculator->calculate($this->employee, 10000, $context);
    }

    public function testInvalidCumulativeIncomeThrowsException(): void
    {
        $context = [
            'current_period' => 2,
            'cumulative_income' => 5000, // 小于当期收入
        ];

        $this->expectException(TaxCalculationException::class);
        $this->expectExceptionMessage('累计收入不能小于当期收入');

        $this->calculator->calculate($this->employee, 10000, $context);
    }

    public function testTaxResultIsValid(): void
    {
        $result = $this->calculator->calculate($this->employee, 10000);

        $this->assertTrue($result->isValid());
        $this->assertGreaterThanOrEqual(0, $result->getTaxAmount());
        $this->assertGreaterThanOrEqual(0, $result->getNetIncome());
    }
}
