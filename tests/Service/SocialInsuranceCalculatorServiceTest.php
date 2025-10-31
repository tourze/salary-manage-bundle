<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\ContributionBase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SocialInsuranceResult;
use Tourze\SalaryManageBundle\Enum\InsuranceType;
use Tourze\SalaryManageBundle\Exception\InsuranceCalculationException;
use Tourze\SalaryManageBundle\Interface\RegionalConfigProviderInterface;
use Tourze\SalaryManageBundle\Service\SocialInsuranceCalculatorService;
use Tourze\SalaryManageBundle\Tests\Service\MockRegionalConfigProvider;

/**
 * @internal
 */
#[CoversClass(SocialInsuranceCalculatorService::class)]
class SocialInsuranceCalculatorServiceTest extends TestCase
{
    private SocialInsuranceCalculatorService $calculator;

    private TestableRegionalConfigProvider $configProvider;

    private Employee $employee;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        $this->configProvider = new TestableRegionalConfigProvider();
        $this->calculator = new SocialInsuranceCalculatorService($this->configProvider);

        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('E001');
        $this->employee->setName('张三');
        $this->employee->setBaseSalary('8000.00');
        $this->employee->setHireDate(new \DateTimeImmutable('2024-01-01'));

        $this->period = new PayrollPeriod();
        $this->period->setYear(2025);
        $this->period->setMonth(1);
    }

    public function testCalculateInsurance(): void
    {
        // 配置返回值
        $this->configProvider->setInsuranceRatesForRegion('default', InsuranceType::Pension, [
            'employer_rate' => 0.20,
            'employee_rate' => 0.08,
        ]);

        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $result = $this->calculator->calculateInsurance(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $contributionBase
        );

        $this->assertInstanceOf(SocialInsuranceResult::class, $result);
        $this->assertEquals(InsuranceType::Pension, $result->getInsuranceType());
        $this->assertEquals(8000.0 * 0.20, $result->getEmployerAmount());
        $this->assertEquals(8000.0 * 0.08, $result->getEmployeeAmount());
    }

    public function testCalculatePensionInsurance(): void
    {
        // 配置返回值
        $this->configProvider->setInsuranceRatesForRegion('beijing', InsuranceType::Pension, [
            'employer_rate' => 0.19,
            'employee_rate' => 0.08,
        ]);

        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: 4800.0,
            maxAmount: 35000.0,
            region: 'beijing',
            year: 2025
        );

        $result = $this->calculator->calculateInsurance(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $contributionBase,
            'beijing'
        );

        $this->assertInstanceOf(SocialInsuranceResult::class, $result);
        $this->assertEquals(InsuranceType::Pension, $result->getInsuranceType());
        $this->assertEquals(8000.0 * 0.19, $result->getEmployerAmount());
        $this->assertEquals(8000.0 * 0.08, $result->getEmployeeAmount());
        $this->assertEquals(0.19, $result->getEmployerRate());
        $this->assertEquals(0.08, $result->getEmployeeRate());
        $this->assertEquals('beijing', $result->getRegion());
    }

    public function testCalculateHousingFund(): void
    {
        $this->configProvider->setInsuranceRatesForRegion('shanghai', InsuranceType::HousingFund, [
            'employer_rate' => 0.07,
            'employee_rate' => 0.07,
        ]);

        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::HousingFund,
            baseAmount: 10000.0,
            minAmount: 2500.0,
            maxAmount: 30000.0,
            region: 'shanghai',
            year: 2025
        );

        $result = $this->calculator->calculateInsurance(
            $this->employee,
            $this->period,
            InsuranceType::HousingFund,
            $contributionBase,
            'shanghai'
        );

        $this->assertEqualsWithDelta(700.0, $result->getEmployerAmount(), 0.01);
        $this->assertEqualsWithDelta(700.0, $result->getEmployeeAmount(), 0.01);
        $this->assertEqualsWithDelta(1400.0, $result->getTotalAmount(), 0.01);
    }

    public function testCalculateWithContributionBaseLimits(): void
    {
        $this->configProvider->setInsuranceRatesForRegion('default', InsuranceType::Pension, [
            'employer_rate' => 0.20,
            'employee_rate' => 0.08,
        ]);

        // 测试超出上限的情况
        $highContributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 50000.0, // 超过上限
            minAmount: 3000.0,
            maxAmount: 30000.0,
            region: 'default',
            year: 2025
        );

        $result = $this->calculator->calculateInsurance(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $highContributionBase
        );

        // 应该按上限30000计算
        $this->assertEquals(30000.0 * 0.20, $result->getEmployerAmount());
        $this->assertEquals(30000.0 * 0.08, $result->getEmployeeAmount());

        // 测试低于下限的情况
        $lowContributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 2000.0, // 低于下限
            minAmount: 3000.0,
            maxAmount: 30000.0,
            region: 'default',
            year: 2025
        );

        $lowResult = $this->calculator->calculateInsurance(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $lowContributionBase
        );

        // 应该按下限3000计算
        $this->assertEquals(3000.0 * 0.20, $lowResult->getEmployerAmount());
        $this->assertEquals(3000.0 * 0.08, $lowResult->getEmployeeAmount());
    }

    public function testCalculateAllInsurance(): void
    {
        // 为每个保险类型设置费率
        $insuranceTypes = [
            InsuranceType::Pension,
            InsuranceType::Medical,
            InsuranceType::Unemployment,
            InsuranceType::WorkInjury,
            InsuranceType::Maternity,
            InsuranceType::HousingFund,
        ];

        foreach ($insuranceTypes as $type) {
            $this->configProvider->setInsuranceRatesForRegion('default', $type, [
                'employer_rate' => $type->getStandardEmployerRate(),
                'employee_rate' => $type->getStandardEmployeeRate(),
            ]);
        }

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
            $this->period,
            $contributionBases
        );

        $this->assertCount(6, $results);
        $this->assertArrayHasKey('pension', $results);
        $this->assertArrayHasKey('medical', $results);
        $this->assertArrayHasKey('unemployment', $results);
        $this->assertArrayHasKey('work_injury', $results);
        $this->assertArrayHasKey('maternity', $results);
        $this->assertArrayHasKey('housing_fund', $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(SocialInsuranceResult::class, $result);
        }
    }

    public function testCalculateTotalTaxDeduction(): void
    {
        $pensionResult = $this->createMockSocialInsuranceResult(InsuranceType::Pension, 640.0);
        $medicalResult = $this->createMockSocialInsuranceResult(InsuranceType::Medical, 160.0);
        $unemploymentResult = $this->createMockSocialInsuranceResult(InsuranceType::Unemployment, 24.0);
        $housingFundResult = $this->createMockSocialInsuranceResult(InsuranceType::HousingFund, 960.0);

        $results = [$pensionResult, $medicalResult, $unemploymentResult, $housingFundResult];

        $totalDeduction = $this->calculator->calculateTotalTaxDeduction($results);

        // 所有五险一金都是税前扣除
        $this->assertEquals(1784.0, $totalDeduction);
    }

    public function testThrowsExceptionForUnsupportedRegion(): void
    {
        $this->configProvider->setSupportedRegions(['default', 'beijing', 'shanghai']);

        $contributionBase = new ContributionBase(
            InsuranceType::Pension,
            8000.0,
            3000.0,
            30000.0
        );

        $this->expectException(InsuranceCalculationException::class);
        $this->expectExceptionMessage('不支持的地区: unsupported');

        $this->calculator->calculateInsurance(
            $this->employee,
            $this->period,
            InsuranceType::Pension,
            $contributionBase,
            'unsupported'
        );
    }

    public function testThrowsExceptionForMissingContributionBase(): void
    {
        // 只设置部分保险类型的费率
        $this->configProvider->setInsuranceRatesForRegion('default', InsuranceType::Pension, [
            'employer_rate' => 0.20,
            'employee_rate' => 0.08,
        ]);
        $this->configProvider->setInsuranceRatesForRegion('default', InsuranceType::Medical, [
            'employer_rate' => 0.10,
            'employee_rate' => 0.02,
        ]);

        // 只提供部分保险类型的缴费基数
        $contributionBases = [
            new ContributionBase(InsuranceType::Pension, 8000.0, 3000.0, 30000.0),
            new ContributionBase(InsuranceType::Medical, 8000.0, 3000.0, 30000.0),
            // 缺少其他保险类型
        ];

        $this->expectException(InsuranceCalculationException::class);
        $this->expectExceptionMessage('缺少失业保险的缴费基数配置');

        $this->calculator->calculateAllInsurance(
            $this->employee,
            $this->period,
            $contributionBases
        );
    }

    public function testValidateContributionBase(): void
    {
        $this->configProvider->setContributionLimitsForRegion('beijing', [
            'min_base' => 4800.0,
            'max_base' => 35000.0,
        ]);

        $validBase = new ContributionBase(
            InsuranceType::Pension,
            8000.0,
            4800.0,
            35000.0
        );

        $this->assertTrue($this->calculator->validateContributionBase($validBase, 'beijing'));

        // 测试一个本身设置不合理的缴费基数
        // 创建一个基数低于地区要求的情况
        $invalidBase = new ContributionBase(
            InsuranceType::Pension,
            2000.0, // 低于最低基数
            1000.0, // 这个contributionBase本身允许的最低基数低于地区要求
            35000.0
        );

        $this->assertFalse($this->calculator->validateContributionBase($invalidBase, 'beijing'));
    }

    public function testGetRegionalRatesWithFallback(): void
    {
        // 不设置任何费率，让方法返回空数组以触发回退逻辑

        $rates = $this->calculator->getRegionalRates('unknown', InsuranceType::Pension);

        // 应该返回标准比例
        $this->assertEquals(0.20, $rates['employer_rate']);
        $this->assertEquals(0.08, $rates['employee_rate']);
    }

    private function createMockSocialInsuranceResult(InsuranceType $type, float $employeeAmount): SocialInsuranceResult
    {
        return new SocialInsuranceResult(
            employee: $this->employee,
            period: $this->period,
            insuranceType: $type,
            contributionBase: new ContributionBase($type, 8000.0, 3000.0, 30000.0),
            employerAmount: 0.0,
            employeeAmount: $employeeAmount,
            employerRate: 0.0,
            employeeRate: $employeeAmount / 8000.0
        );
    }
}
