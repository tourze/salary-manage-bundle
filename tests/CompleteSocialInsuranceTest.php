<?php

namespace Tourze\SalaryManageBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\ContributionBase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SocialInsuranceResult;
use Tourze\SalaryManageBundle\Enum\InsuranceType;
use Tourze\SalaryManageBundle\Service\DefaultRegionalConfigProvider;
use Tourze\SalaryManageBundle\Service\SocialInsuranceCalculatorService;

/**
 * @internal
 */
#[CoversClass(SocialInsuranceCalculatorService::class)]
class CompleteSocialInsuranceTest extends TestCase
{
    private SocialInsuranceCalculatorService $calculator;

    private Employee $employee;

    protected function setUp(): void
    {
        $this->calculator = new SocialInsuranceCalculatorService(new DefaultRegionalConfigProvider());

        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('E001');
        $this->employee->setName('李四');
        $this->employee->setBaseSalary('12000.00');
        $this->employee->setDepartment('技术部');
        $this->employee->setHireDate(new \DateTimeImmutable('2023-01-01'));
    }

    public function testCompleteBeijingSocialInsuranceCalculation(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(3);

        // 北京地区12000元工资的五险一金计算
        $contributionBases = [
            new ContributionBase(InsuranceType::Pension, 12000.0, 4800.0, 35000.0, 'beijing', 2025),
            new ContributionBase(InsuranceType::Medical, 12000.0, 4800.0, 35000.0, 'beijing', 2025),
            new ContributionBase(InsuranceType::Unemployment, 12000.0, 4800.0, 35000.0, 'beijing', 2025),
            new ContributionBase(InsuranceType::WorkInjury, 12000.0, 4800.0, 35000.0, 'beijing', 2025),
            new ContributionBase(InsuranceType::Maternity, 12000.0, 4800.0, 35000.0, 'beijing', 2025),
            new ContributionBase(InsuranceType::HousingFund, 12000.0, 2000.0, 28000.0, 'beijing', 2025),
        ];

        $results = $this->calculator->calculateAllInsurance(
            $this->employee,
            $period,
            $contributionBases,
            'beijing'
        );

        $this->assertCount(6, $results);

        // 验证养老保险
        $pensionResult = $results['pension'];
        $this->assertEquals(InsuranceType::Pension, $pensionResult->getInsuranceType());
        $this->assertEqualsWithDelta(12000 * 0.19, $pensionResult->getEmployerAmount(), 0.01); // 企业19%
        $this->assertEqualsWithDelta(12000 * 0.08, $pensionResult->getEmployeeAmount(), 0.01); // 个人8%

        // 验证医疗保险
        $medicalResult = $results['medical'];
        $this->assertEqualsWithDelta(12000 * 0.095, $medicalResult->getEmployerAmount(), 0.01); // 企业9.5%
        $this->assertEqualsWithDelta(12000 * 0.02, $medicalResult->getEmployeeAmount(), 0.01);  // 个人2%

        // 验证失业保险
        $unemploymentResult = $results['unemployment'];
        $this->assertEqualsWithDelta(12000 * 0.008, $unemploymentResult->getEmployerAmount(), 0.01); // 企业0.8%
        $this->assertEqualsWithDelta(12000 * 0.002, $unemploymentResult->getEmployeeAmount(), 0.01);  // 个人0.2%

        // 验证工伤保险（个人不缴费）
        $workInjuryResult = $results['work_injury'];
        $this->assertEqualsWithDelta(12000 * 0.002, $workInjuryResult->getEmployerAmount(), 0.01); // 企业0.2%
        $this->assertEquals(0.0, $workInjuryResult->getEmployeeAmount()); // 个人不缴费

        // 验证生育保险（个人不缴费）
        $maternityResult = $results['maternity'];
        $this->assertEqualsWithDelta(12000 * 0.008, $maternityResult->getEmployerAmount(), 0.01); // 企业0.8%
        $this->assertEquals(0.0, $maternityResult->getEmployeeAmount()); // 个人不缴费

        // 验证住房公积金
        $housingFundResult = $results['housing_fund'];
        $this->assertEqualsWithDelta(12000 * 0.12, $housingFundResult->getEmployerAmount(), 0.01); // 企业12%
        $this->assertEqualsWithDelta(12000 * 0.12, $housingFundResult->getEmployeeAmount(), 0.01); // 个人12%
    }

    public function testCompleteShanghaiSocialInsuranceCalculation(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(3);

        // 上海地区15000元工资的五险一金计算
        $baseSalary = 15000.0;
        $contributionBases = [
            new ContributionBase(InsuranceType::Pension, $baseSalary, 5500.0, 36000.0, 'shanghai', 2025),
            new ContributionBase(InsuranceType::Medical, $baseSalary, 5500.0, 36000.0, 'shanghai', 2025),
            new ContributionBase(InsuranceType::Unemployment, $baseSalary, 5500.0, 36000.0, 'shanghai', 2025),
            new ContributionBase(InsuranceType::WorkInjury, $baseSalary, 5500.0, 36000.0, 'shanghai', 2025),
            new ContributionBase(InsuranceType::Maternity, $baseSalary, 5500.0, 36000.0, 'shanghai', 2025),
            new ContributionBase(InsuranceType::HousingFund, $baseSalary, 2500.0, 30000.0, 'shanghai', 2025),
        ];

        $results = $this->calculator->calculateAllInsurance(
            $this->employee,
            $period,
            $contributionBases,
            'shanghai'
        );

        // 计算总的个人缴费（税前扣除金额）
        $totalTaxDeduction = $this->calculator->calculateTotalTaxDeduction(array_values($results));

        // 上海地区个人缴费: 8%+2%+0.5%+0%+0%+7% = 17.5%
        $expectedPersonalTotal = $baseSalary * (0.08 + 0.02 + 0.005 + 0.0 + 0.0 + 0.07);
        $this->assertEqualsWithDelta($expectedPersonalTotal, $totalTaxDeduction, 0.01);

        // 验证每项保险都可以税前扣除
        foreach ($results as $result) {
            $this->assertTrue($result->getInsuranceType()->isTaxDeductible());
        }
    }

    public function testHighSalaryContributionBaseLimits(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(3);

        // 测试高薪员工（50000元）在北京的社保计算
        $highSalary = 50000.0;
        $contributionBases = [
            new ContributionBase(InsuranceType::Pension, $highSalary, 4800.0, 35000.0, 'beijing', 2025),
            new ContributionBase(InsuranceType::Medical, $highSalary, 4800.0, 35000.0, 'beijing', 2025),
            new ContributionBase(InsuranceType::Unemployment, $highSalary, 4800.0, 35000.0, 'beijing', 2025),
            new ContributionBase(InsuranceType::WorkInjury, $highSalary, 4800.0, 35000.0, 'beijing', 2025),
            new ContributionBase(InsuranceType::Maternity, $highSalary, 4800.0, 35000.0, 'beijing', 2025),
            new ContributionBase(InsuranceType::HousingFund, $highSalary, 2000.0, 28000.0, 'beijing', 2025),
        ];

        $results = $this->calculator->calculateAllInsurance(
            $this->employee,
            $period,
            $contributionBases,
            'beijing'
        );

        // 验证社会保险按35000封顶计算
        $pensionResult = $results['pension'];
        $this->assertEqualsWithDelta(35000 * 0.19, $pensionResult->getEmployerAmount(), 0.01);
        $this->assertEqualsWithDelta(35000 * 0.08, $pensionResult->getEmployeeAmount(), 0.01);

        // 验证住房公积金按28000封顶计算
        $housingFundResult = $results['housing_fund'];
        $this->assertEqualsWithDelta(28000 * 0.12, $housingFundResult->getEmployerAmount(), 0.01);
        $this->assertEqualsWithDelta(28000 * 0.12, $housingFundResult->getEmployeeAmount(), 0.01);
    }

    public function testLowSalaryContributionBaseLimits(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(3);

        // 测试低薪员工（2000元）在深圳的社保计算
        $lowSalary = 2000.0;
        $contributionBases = [
            new ContributionBase(InsuranceType::Pension, $lowSalary, 4500.0, 34000.0, 'shenzhen', 2025),
            new ContributionBase(InsuranceType::Medical, $lowSalary, 4500.0, 34000.0, 'shenzhen', 2025),
            new ContributionBase(InsuranceType::Unemployment, $lowSalary, 4500.0, 34000.0, 'shenzhen', 2025),
            new ContributionBase(InsuranceType::WorkInjury, $lowSalary, 4500.0, 34000.0, 'shenzhen', 2025),
            new ContributionBase(InsuranceType::Maternity, $lowSalary, 4500.0, 34000.0, 'shenzhen', 2025),
            new ContributionBase(InsuranceType::HousingFund, $lowSalary, 2000.0, 27000.0, 'shenzhen', 2025),
        ];

        $results = $this->calculator->calculateAllInsurance(
            $this->employee,
            $period,
            $contributionBases,
            'shenzhen'
        );

        // 验证社会保险按最低基数4500计算
        $pensionResult = $results['pension'];
        $this->assertEqualsWithDelta(4500 * 0.13, $pensionResult->getEmployerAmount(), 0.01); // 深圳养老13%
        $this->assertEqualsWithDelta(4500 * 0.08, $pensionResult->getEmployeeAmount(), 0.01);

        // 验证住房公积金按最低基数2000计算
        $housingFundResult = $results['housing_fund'];
        $this->assertEqualsWithDelta(2000 * 0.13, $housingFundResult->getEmployerAmount(), 0.01); // 深圳公积金13%
        $this->assertEqualsWithDelta(2000 * 0.13, $housingFundResult->getEmployeeAmount(), 0.01);
    }

    public function testCompleteCalculationResults(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(3);
        $baseSalary = 10000.0;

        $contributionBases = [
            new ContributionBase(InsuranceType::Pension, $baseSalary, 4200.0, 32000.0, 'guangzhou', 2025),
            new ContributionBase(InsuranceType::Medical, $baseSalary, 4200.0, 32000.0, 'guangzhou', 2025),
            new ContributionBase(InsuranceType::Unemployment, $baseSalary, 4200.0, 32000.0, 'guangzhou', 2025),
            new ContributionBase(InsuranceType::WorkInjury, $baseSalary, 4200.0, 32000.0, 'guangzhou', 2025),
            new ContributionBase(InsuranceType::Maternity, $baseSalary, 4200.0, 32000.0, 'guangzhou', 2025),
            new ContributionBase(InsuranceType::HousingFund, $baseSalary, 1800.0, 25000.0, 'guangzhou', 2025),
        ];

        $results = $this->calculator->calculateAllInsurance(
            $this->employee,
            $period,
            $contributionBases,
            'guangzhou'
        );

        // 验证计算结果的完整性和准确性
        foreach ($results as $insuranceType => $result) {
            // 验证基本属性
            $this->assertEquals($this->employee, $result->getEmployee());
            $this->assertEquals($period, $result->getPeriod());
            $this->assertEquals('guangzhou', $result->getRegion());
            $this->assertTrue($result->isValid()); // 验证计算合理性

            // 验证显示信息
            $displayInfo = $result->getDisplayInfo();
            $this->assertArrayHasKey('insurance_type', $displayInfo);
            $this->assertArrayHasKey('contribution_base', $displayInfo);
            $this->assertArrayHasKey('employer_amount', $displayInfo);
            $this->assertArrayHasKey('employee_amount', $displayInfo);
            $this->assertArrayHasKey('total_amount', $displayInfo);

            // 验证金额为正数
            $this->assertGreaterThanOrEqual(0, $result->getEmployerAmount());
            $this->assertGreaterThanOrEqual(0, $result->getEmployeeAmount());
            $this->assertGreaterThanOrEqual(0, $result->getTotalAmount());
        }
    }

    public function testCalculateAllInsurance(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $contributionBases = [
            new ContributionBase(InsuranceType::Pension, 8000.0, 3000.0, 30000.0),
            new ContributionBase(InsuranceType::Medical, 8000.0, 3000.0, 30000.0),
            new ContributionBase(InsuranceType::Unemployment, 8000.0, 3000.0, 30000.0),
            new ContributionBase(InsuranceType::WorkInjury, 8000.0, 3000.0, 30000.0),
            new ContributionBase(InsuranceType::Maternity, 8000.0, 3000.0, 30000.0),
            new ContributionBase(InsuranceType::HousingFund, 8000.0, 1500.0, 25000.0),
        ];

        $results = $this->calculator->calculateAllInsurance(
            $this->employee,
            $period,
            $contributionBases
        );

        $this->assertCount(6, $results);
        $this->assertArrayHasKey('pension', $results);
        $this->assertArrayHasKey('housing_fund', $results);
    }

    public function testCalculateInsurance(): void
    {
        $contributionBase = new ContributionBase(
            InsuranceType::Pension,
            8000.0,
            3000.0,
            30000.0
        );

        $result = $this->calculator->calculateInsurance(
            $this->employee,
            (function() { $period = new PayrollPeriod(); $period->setYear(2025); $period->setMonth(1); return $period; })(),
            InsuranceType::Pension,
            $contributionBase
        );

        $this->assertInstanceOf(SocialInsuranceResult::class, $result);
        $this->assertEquals(InsuranceType::Pension, $result->getInsuranceType());
    }

    public function testCalculateTotalTaxDeduction(): void
    {
        $results = [
            $this->createMockResult(InsuranceType::Pension, 640.0),
            $this->createMockResult(InsuranceType::Medical, 160.0),
            $this->createMockResult(InsuranceType::HousingFund, 960.0),
        ];

        $totalDeduction = $this->calculator->calculateTotalTaxDeduction($results);

        $this->assertEquals(1760.0, $totalDeduction);
    }

    public function testValidateContributionBase(): void
    {
        $validBase = new ContributionBase(
            InsuranceType::Pension,
            8000.0,
            4800.0,
            35000.0
        );

        $isValid = $this->calculator->validateContributionBase($validBase, 'beijing');
        $this->assertTrue($isValid);
    }

    private function createMockResult(InsuranceType $type, float $employeeAmount): SocialInsuranceResult
    {
        return new SocialInsuranceResult(
            employee: $this->employee,
            period: (function() { $period = new PayrollPeriod(); $period->setYear(2025); $period->setMonth(1); return $period; })(),
            insuranceType: $type,
            contributionBase: new ContributionBase($type, 8000.0, 3000.0, 30000.0),
            employerAmount: 0.0,
            employeeAmount: $employeeAmount,
            employerRate: 0.0,
            employeeRate: $employeeAmount / 8000.0
        );
    }
}
