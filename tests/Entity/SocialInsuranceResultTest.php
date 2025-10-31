<?php

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\ContributionBase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SocialInsuranceResult;
use Tourze\SalaryManageBundle\Enum\InsuranceType;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * @internal
 */
#[CoversClass(SocialInsuranceResult::class)]
final class SocialInsuranceResultTest extends TestCase
{
    private Employee $employee;

    private PayrollPeriod $period;

    private ContributionBase $contributionBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('EMP001');
        $this->employee->setName('测试员工');
        $this->employee->setBaseSalary('10000.00');
        $this->employee->setHireDate(new \DateTimeImmutable('2024-01-01'));

        $this->period = new PayrollPeriod();
        $this->period->setYear(2025);
        $this->period->setMonth(1);

        $this->contributionBase = new ContributionBase(
            InsuranceType::Pension,
            8000.0,  // baseAmount
            3000.0, // minAmount
            25000.0, // maxAmount
            'beijing',
            2025
        );
    }

    public function testConstructorWithValidParameters(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $this->contributionBase,
            1600.0, // 企业缴费 20%
            640.0,  // 个人缴费 8%
            0.20,   // 企业费率
            0.08,   // 个人费率
            'beijing',
            ['calculation_method' => 'standard'],
            ['updated_at' => '2025-01-15']
        );

        $this->assertEquals($this->employee, $result->getEmployee());
        $this->assertEquals($this->period, $result->getPeriod());
        $this->assertEquals(InsuranceType::Pension, $result->getInsuranceType());
        $this->assertEquals($this->contributionBase, $result->getContributionBase());
        $this->assertEquals(1600.0, $result->getEmployerAmount());
        $this->assertEquals(640.0, $result->getEmployeeAmount());
        $this->assertEquals(0.20, $result->getEmployerRate());
        $this->assertEquals(0.08, $result->getEmployeeRate());
        $this->assertEquals('beijing', $result->getRegion());
        $this->assertEquals(['calculation_method' => 'standard'], $result->getCalculationDetails());
        $this->assertEquals(['updated_at' => '2025-01-15'], $result->getMetadata());
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $contributionBase = new ContributionBase(
            InsuranceType::Medical,
            8000.0,  // baseAmount
            3000.0, // minAmount
            25000.0, // maxAmount
            'beijing',
            2025
        );

        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Medical,
            $this->contributionBase,
            800.0,
            160.0,
            0.10,
            0.02
        );

        $this->assertEquals('default', $result->getRegion());
        $this->assertEquals([], $result->getCalculationDetails());
        $this->assertEquals([], $result->getMetadata());
    }

    public function testConstructorThrowsExceptionForNegativeEmployerAmount(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('缴费金额不能为负数');

        new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $this->contributionBase,
            -100.0, // 负数企业缴费
            640.0,
            0.20,
            0.08
        );
    }

    public function testConstructorThrowsExceptionForNegativeEmployeeAmount(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('缴费金额不能为负数');

        new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $this->contributionBase,
            1600.0,
            -50.0, // 负数个人缴费
            0.20,
            0.08
        );
    }

    #[TestWith([-0.1, 0.08])]
    #[TestWith([0.20, -0.02])]
    #[TestWith([1.5, 0.08])]
    #[TestWith([0.20, 1.2])]
    #[TestWith([-0.1, -0.02])]
    #[TestWith([1.2, 1.5])]
    public function testConstructorThrowsExceptionForInvalidRates(
        float $employerRate,
        float $employeeRate,
    ): void {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('缴费比例必须在0-1之间');

        new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $this->contributionBase,
            1600.0,
            640.0,
            $employerRate,
            $employeeRate
        );
    }

    public function testGetTotalAmount(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Medical,
            $this->contributionBase,
            800.0,  // 企业缴费
            160.0,  // 个人缴费
            0.10,
            0.02
        );

        $this->assertEquals(960.0, $result->getTotalAmount());
    }

    public function testGetEmployerBurdenRatio(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $this->contributionBase,
            1600.0, // 企业缴费
            400.0,  // 个人缴费
            0.20,
            0.05
        );

        // 1600 / (1600 + 400) = 1600 / 2000 = 0.8
        $this->assertEquals(0.8, $result->getEmployerBurdenRatio());
    }

    public function testGetEmployerBurdenRatioWithZeroTotal(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $this->contributionBase,
            0.0,
            0.0,
            0.0,
            0.0
        );

        $this->assertEquals(0.0, $result->getEmployerBurdenRatio());
    }

    #[TestWith([InsuranceType::Pension, 640.0, true, 640.0], 'pension deductible')]
    #[TestWith([InsuranceType::Medical, 160.0, true, 160.0], 'medical deductible')]
    #[TestWith([InsuranceType::Unemployment, 40.0, true, 40.0], 'unemployment deductible')]
    #[TestWith([InsuranceType::HousingFund, 800.0, true, 800.0], 'housing fund deductible')]
    public function testGetTaxDeductibleAmount(
        InsuranceType $insuranceType,
        float $employeeAmount,
        bool $isTaxDeductible,
        float $expectedDeduction,
    ): void {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            $insuranceType,
            $this->contributionBase,
            1000.0,
            $employeeAmount,
            0.10,
            0.05
        );

        $this->assertEquals($expectedDeduction, $result->getTaxDeductibleAmount());
    }

    public function testIsValidWithCorrectCalculation(): void
    {
        $contributionBase = new ContributionBase(
            InsuranceType::Pension,
            10000.0, // baseAmount
            3000.0, // minAmount
            25000.0, // maxAmount
            'beijing',
            2025
        );

        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $this->contributionBase,
            1600.0, // 8000 * 0.20
            640.0,  // 8000 * 0.08
            0.20,
            0.08
        );

        $this->assertTrue($result->isValid());
    }

    public function testIsValidWithIncorrectCalculation(): void
    {
        $contributionBase = new ContributionBase(
            InsuranceType::Pension,
            10000.0, // baseAmount
            3000.0, // minAmount
            25000.0, // maxAmount
            'beijing',
            2025
        );

        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $this->contributionBase,
            1950.0, // 应该是 2000.0 (10000 * 0.20)
            800.0,  // 正确
            0.20,
            0.08
        );

        $this->assertFalse($result->isValid());
    }

    public function testIsValidWithFloatingPointTolerance(): void
    {
        $contributionBase = new ContributionBase(
            InsuranceType::Pension,
            10000.0, // baseAmount
            3000.0, // minAmount
            25000.0, // maxAmount
            'beijing',
            2025
        );

        // 允许0.01的浮点误差
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $contributionBase,
            2000.005, // 在误差范围内
            799.999,  // 在误差范围内
            0.20,
            0.08
        );

        $this->assertTrue($result->isValid());
    }

    public function testGetDisplayInfo(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $this->contributionBase,
            1600.0,
            640.0,
            0.20,
            0.08,
            'beijing'
        );

        $displayInfo = $result->getDisplayInfo();

        $this->assertEquals('养老保险', $displayInfo['insurance_type']);
        $this->assertEquals('8,000.00', $displayInfo['contribution_base']);
        $this->assertEquals('1,600.00', $displayInfo['employer_amount']);
        $this->assertEquals('640.00', $displayInfo['employee_amount']);
        $this->assertEquals('2,240.00', $displayInfo['total_amount']);
        $this->assertEquals('20.00%', $displayInfo['employer_rate']);
        $this->assertEquals('8.00%', $displayInfo['employee_rate']);
        $this->assertEquals('beijing', $displayInfo['region']);
        $this->assertEquals('2025-01', $displayInfo['period']);
    }

    public function testGetTotalPersonalContribution(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Medical,
            $this->contributionBase,
            800.0,
            160.0,
            0.10,
            0.02
        );

        $this->assertEquals(160.0, $result->getTotalPersonalContribution());
    }

    public function testGetTotalCompanyContribution(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Medical,
            $this->contributionBase,
            800.0,
            160.0,
            0.10,
            0.02
        );

        $this->assertEquals(800.0, $result->getTotalCompanyContribution());
    }

    public function testGetPensionContribution(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $this->contributionBase,
            800.0,
            200.0,
            0.10,
            0.025
        );

        $this->assertEquals(1000.0, $result->getPensionContribution());
    }

    public function testGetMedicalContribution(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Medical,
            $this->contributionBase,
            800.0,
            200.0,
            0.10,
            0.025
        );

        $this->assertEquals(1000.0, $result->getMedicalContribution());
    }

    public function testGetUnemploymentContribution(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Unemployment,
            $this->contributionBase,
            800.0,
            200.0,
            0.10,
            0.025
        );

        $this->assertEquals(1000.0, $result->getUnemploymentContribution());
    }

    public function testGetWorkInjuryContribution(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::WorkInjury,
            $this->contributionBase,
            800.0,
            200.0,
            0.10,
            0.025
        );

        $this->assertEquals(1000.0, $result->getWorkInjuryContribution());
    }

    public function testGetMaternityContribution(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Maternity,
            $this->contributionBase,
            800.0,
            200.0,
            0.10,
            0.025
        );

        $this->assertEquals(1000.0, $result->getMaternityContribution());
    }

    public function testGetHousingFundContribution(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::HousingFund,
            $this->contributionBase,
            800.0,
            200.0,
            0.10,
            0.025
        );

        $this->assertEquals(1000.0, $result->getHousingFundContribution());
    }

    public function testSpecificInsuranceContributionsReturnZeroForOtherTypes(): void
    {
        $pensionResult = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension, // 设置为养老保险
            $this->contributionBase,
            800.0,
            200.0,
            0.10,
            0.025
        );

        // 其他保险类型应该返回 0
        $this->assertEquals(0.0, $pensionResult->getMedicalContribution());
        $this->assertEquals(0.0, $pensionResult->getUnemploymentContribution());
        $this->assertEquals(0.0, $pensionResult->getWorkInjuryContribution());
        $this->assertEquals(0.0, $pensionResult->getMaternityContribution());
        $this->assertEquals(0.0, $pensionResult->getHousingFundContribution());
    }

    public function testComplexCalculationScenario(): void
    {
        $contributionBase = new ContributionBase(
            InsuranceType::HousingFund,
            15000.0, // baseAmount
            3000.0, // minAmount
            30000.0, // maxAmount
            'beijing',
            2025
        );

        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::HousingFund,
            $contributionBase, // 使用上面创建的 contributionBase
            1800.0, // 15000 * 0.12
            1800.0, // 15000 * 0.12 (公积金企业和个人比例相同)
            0.12,
            0.12,
            'shanghai',
            [
                'calculation_method' => 'monthly',
                'adjustment_factor' => 1.0,
                'cap_applied' => false,
            ],
            [
                'policy_version' => '2025.1',
                'last_updated' => '2025-01-01',
            ]
        );

        $this->assertEquals(3600.0, $result->getTotalAmount());
        $this->assertEquals(0.5, $result->getEmployerBurdenRatio());
        $this->assertEquals(1800.0, $result->getTaxDeductibleAmount());
        $this->assertTrue($result->isValid());
        $this->assertEquals(3600.0, $result->getHousingFundContribution());
    }

    public function testReadOnlyNature(): void
    {
        $result = new SocialInsuranceResult(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $this->contributionBase,
            1600.0,
            640.0,
            0.20,
            0.08
        );

        // 验证所有方法都可以调用且返回正确类型
        $this->assertInstanceOf(Employee::class, $result->getEmployee());
        $this->assertInstanceOf(PayrollPeriod::class, $result->getPeriod());
        $this->assertInstanceOf(InsuranceType::class, $result->getInsuranceType());
        $this->assertInstanceOf(ContributionBase::class, $result->getContributionBase());
        $this->assertIsFloat($result->getEmployerAmount());
        $this->assertIsFloat($result->getEmployeeAmount());
        $this->assertIsFloat($result->getEmployerRate());
        $this->assertIsFloat($result->getEmployeeRate());
        $this->assertIsString($result->getRegion());
        $this->assertIsArray($result->getCalculationDetails());
        $this->assertIsArray($result->getMetadata());
        $this->assertIsFloat($result->getTotalAmount());
        $this->assertIsFloat($result->getEmployerBurdenRatio());
        $this->assertIsFloat($result->getTaxDeductibleAmount());
        $this->assertIsBool($result->isValid());
        $this->assertIsArray($result->getDisplayInfo());
    }
}
