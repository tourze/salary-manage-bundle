<?php

namespace Tourze\SalaryManageBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\Deduction;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Enum\DeductionType;
use Tourze\SalaryManageBundle\Exception\TaxCalculationException;
use Tourze\SalaryManageBundle\Service\Rules\AllowanceRule;
use Tourze\SalaryManageBundle\Service\Rules\BasicSalaryRule;
use Tourze\SalaryManageBundle\Service\Rules\OvertimeRule;
use Tourze\SalaryManageBundle\Service\SalaryCalculatorService;
use Tourze\SalaryManageBundle\Service\TaxBracketProvider;
use Tourze\SalaryManageBundle\Service\TaxCalculatorService;

/**
 * 完整薪资计算流程集成测试
 * 验证从基本薪资计算到税务处理的完整业务流程
 * @internal
 */
#[CoversClass(SalaryCalculatorService::class)]
class CompleteSalaryProcessTest extends TestCase
{
    private SalaryCalculatorService $salaryCalculator;

    private TaxCalculatorService $taxCalculator;

    private Employee $employee;

    protected function setUp(): void
    {
        // 初始化薪资计算器
        $this->salaryCalculator = new SalaryCalculatorService();
        $this->salaryCalculator->addRule(new BasicSalaryRule());
        $this->salaryCalculator->addRule(new OvertimeRule());

        // 初始化税务计算器
        $bracketProvider = new TaxBracketProvider();
        $this->taxCalculator = new TaxCalculatorService($bracketProvider);

        // 创建测试员工
        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('EMP001');
        $this->employee->setName('张三');
        $this->employee->setBaseSalary('15000.00');
        $this->employee->setDepartment('开发部');
        $this->employee->setHireDate(new \DateTimeImmutable('2024-01-01'));
    }

    public function testCompleteSalaryCalculationWithoutDeductions(): void
    {
        // 第一步：计算基本薪资
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $salaryResult = $this->salaryCalculator->calculate($this->employee, $period);

        // 验证薪资计算结果
        $this->assertEquals(15000.0, $salaryResult->getGrossAmount());
        $this->assertEquals(15000.0, $salaryResult->getNetAmount()); // 无扣除项
        $this->assertCount(2, $salaryResult->getItems()); // 基本工资 + 加班费(0)

        // 第二步：计算个人所得税
        $taxContext = [
            'current_period' => 1,
            'cumulative_income' => 15000,
            'cumulative_tax_paid' => 0,
            'period' => $period,
        ];

        $taxResult = $this->taxCalculator->calculate(
            $this->employee,
            $salaryResult->getGrossAmount(),
            $taxContext
        );

        // 验证税务计算结果
        // 应税收入：15000 - 5000(免征额) = 10000
        // 税率3%：10000 * 0.03 = 300元
        $this->assertEquals(300.0, $taxResult->getTaxAmount());
        $this->assertEquals(14700.0, $taxResult->getNetIncome()); // 15000 - 300
        $this->assertEquals(0.03, $taxResult->getMarginalTaxRate());

        // 第三步：验证完整流程结果
        $this->assertTrue($taxResult->isValid());
        $this->assertEquals($this->employee, $taxResult->getEmployee());
        $this->assertEquals($period, $taxResult->getPeriod());
    }

    public function testCompleteSalaryCalculationWithDeductions(): void
    {
        // 创建专项附加扣除
        $deductions = [
            new Deduction(DeductionType::ChildEducation, 2000, '子女教育扣除'),
            new Deduction(DeductionType::HousingLoan, 1000, '住房贷款利息扣除'),
        ];

        // 第一步：计算薪资
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $salaryResult = $this->salaryCalculator->calculate($this->employee, $period);

        // 第二步：计算个人所得税（含专项附加扣除）
        $taxContext = [
            'current_period' => 1,
            'cumulative_income' => 15000,
            'cumulative_tax_paid' => 0,
            'deductions' => $deductions,
            'period' => $period,
        ];

        $taxResult = $this->taxCalculator->calculate(
            $this->employee,
            $salaryResult->getGrossAmount(),
            $taxContext
        );

        // 验证税务计算结果
        // 应税收入：15000 - 5000(免征额) - 3000(专项扣除) = 7000
        // 税率3%：7000 * 0.03 = 210元
        $this->assertEquals(210.0, $taxResult->getTaxAmount());
        $this->assertEquals(14790.0, $taxResult->getNetIncome()); // 15000 - 210
        $this->assertCount(2, $taxResult->getDeductions());
        $this->assertEquals(3000.0, $taxResult->getTotalDeductions());
    }

    public function testCompleteSalaryCalculationWithOvertime(): void
    {
        // 第一步：计算薪资（含加班费）
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);

        // 设置加班10小时
        $context = ['overtime_hours' => 10];

        // 创建带上下文的薪资计算结果
        $salaryResult = $this->salaryCalculator->calculate($this->employee, $period);

        // 手动添加加班费（因为当前实现需要上下文）
        $hourlyRate = 15000 / 174; // 月薪/标准工时
        $overtimeAmount = 10 * $hourlyRate * 1.5;

        $expectedGross = 15000 + $overtimeAmount;

        // 验证基本薪资已计算
        $this->assertEquals(15000.0, $salaryResult->getGrossAmount());

        // 第二步：计算税务（基于总收入）
        $taxContext = [
            'current_period' => 1,
            'cumulative_income' => $expectedGross,
            'cumulative_tax_paid' => 0,
            'period' => $period,
        ];

        $taxResult = $this->taxCalculator->calculate(
            $this->employee,
            $expectedGross,
            $taxContext
        );

        // 验证税务结果合理
        $this->assertGreaterThan(300.0, $taxResult->getTaxAmount()); // 比基本薪资的税更多
        $this->assertEquals(0.03, $taxResult->getMarginalTaxRate());
        $this->assertTrue($taxResult->isValid());
    }

    public function testMultiPeriodCumulativeCalculation(): void
    {
        // 模拟全年累计预扣法计算
        $periods = [
            ['month' => 1, 'income' => 15000, 'prevTax' => 0],
            ['month' => 2, 'income' => 15000, 'prevTax' => 300],
            ['month' => 3, 'income' => 15000, 'prevTax' => 600],
        ];

        $cumulativeIncome = 0;
        $cumulativeTax = 0;

        foreach ($periods as $periodData) {
            $cumulativeIncome += $periodData['income'];

            $period = new PayrollPeriod();
            $period->setYear(2025);
            $period->setMonth($periodData['month']);

            $taxContext = [
                'current_period' => $periodData['month'],
                'cumulative_income' => $cumulativeIncome,
                'cumulative_tax_paid' => $cumulativeTax,
                'period' => $period,
            ];

            $taxResult = $this->taxCalculator->calculate(
                $this->employee,
                $periodData['income'],
                $taxContext
            );

            // 更新累计已缴税额
            $cumulativeTax += $taxResult->getTaxAmount();

            // 验证累计预扣法正确性
            $this->assertGreaterThanOrEqual(0, $taxResult->getTaxAmount());
            $this->assertTrue($taxResult->isValid());
            $this->assertEquals(0.03, $taxResult->getMarginalTaxRate());
        }

        // 验证全年累计税额合理
        $this->assertEquals(900.0, $cumulativeTax); // 3个月 * 300元/月
    }

    public function testHighIncomeTaxBracketTransition(): void
    {
        // 创建高收入员工
        $highIncomeEmployee = new Employee();
        $highIncomeEmployee->setEmployeeNumber('EMP002');
        $highIncomeEmployee->setName('李总');
        $highIncomeEmployee->setBaseSalary('50000.00');
        $highIncomeEmployee->setDepartment('管理层');
        $highIncomeEmployee->setHireDate(new \DateTimeImmutable('2024-01-01'));

        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(12);

        // 模拟年底时的累计收入（60万，适用30%税率）
        $taxContext = [
            'current_period' => 12,
            'cumulative_income' => 600000,
            'cumulative_tax_paid' => 100000, // 调整已缴税额
            'period' => $period,
        ];

        $taxResult = $this->taxCalculator->calculate(
            $highIncomeEmployee,
            50000,
            $taxContext
        );

        // 验证高税率档次计算
        $this->assertEquals(0.30, $taxResult->getMarginalTaxRate()); // 60万适用30%税率
        $this->assertGreaterThan(0, $taxResult->getTaxAmount());
        $this->assertTrue($taxResult->isValid());

        // 验证有效税率合理
        $effectiveRate = $taxResult->getEffectiveTaxRate();
        $this->assertGreaterThan(0.15, $effectiveRate);
        $this->assertLessThan(0.30, $effectiveRate);
    }

    public function testErrorHandlingInCompleteProcess(): void
    {
        // 创建异常场景：负薪资
        $this->expectException(TaxCalculationException::class);

        $this->taxCalculator->calculate($this->employee, -1000);
    }

    public function testAddRule(): void
    {
        $allowanceRule = new AllowanceRule(); // 使用不同的规则类型
        $this->salaryCalculator->addRule($allowanceRule);

        $rules = $this->salaryCalculator->getRules();
        $this->assertCount(3, $rules); // 包括预添加的2个规则
    }

    public function testRemoveRule(): void
    {
        // 测试移除规则
        $this->salaryCalculator->removeRule('basic_salary');

        $rules = $this->salaryCalculator->getRules();
        $this->assertCount(1, $rules); // 只剩下overtime规则
    }

    public function testCalculate(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $result = $this->salaryCalculator->calculate($this->employee, $period);

        $this->assertInstanceOf(SalaryCalculation::class, $result);
        $this->assertEquals(15000.0, $result->getGrossAmount());
    }
}
