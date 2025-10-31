<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Enum\InsuranceType;
use Tourze\SalaryManageBundle\Service\DefaultRegionalConfigProvider;

/**
 * 默认地区配置提供者测试
 * 验收标准：测试各地区社保公积金配置的正确性
 * @internal
 */
#[CoversClass(DefaultRegionalConfigProvider::class)]
class DefaultRegionalConfigProviderTest extends TestCase
{
    private DefaultRegionalConfigProvider $configProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider = new DefaultRegionalConfigProvider();
    }

    public function testGetInsuranceRatesWithBeijingPensionShouldReturnCorrectRates(): void
    {
        $rates = $this->configProvider->getInsuranceRates('beijing', InsuranceType::Pension);

        $expectedRates = [
            'employer_rate' => 0.19,
            'employee_rate' => 0.08,
        ];

        $this->assertEquals($expectedRates, $rates);
    }

    public function testGetInsuranceRatesWithShanghaiMedicalShouldReturnCorrectRates(): void
    {
        $rates = $this->configProvider->getInsuranceRates('shanghai', InsuranceType::Medical);

        $expectedRates = [
            'employer_rate' => 0.095,
            'employee_rate' => 0.02,
        ];

        $this->assertEquals($expectedRates, $rates);
    }

    public function testGetInsuranceRatesWithUnsupportedRegionShouldReturnDefaultRates(): void
    {
        $rates = $this->configProvider->getInsuranceRates('unsupported_region', InsuranceType::Pension);

        $expectedRates = [
            'employer_rate' => 0.20,
            'employee_rate' => 0.08,
        ];

        $this->assertEquals($expectedRates, $rates);
    }

    public function testGetInsuranceRatesWithDefaultRegionHousingFundShouldReturnStandardRates(): void
    {
        $rates = $this->configProvider->getInsuranceRates('default', InsuranceType::HousingFund);

        $expectedRates = [
            'employer_rate' => 0.12,
            'employee_rate' => 0.12,
        ];

        $this->assertEquals($expectedRates, $rates);
    }

    public function testGetContributionLimitsWithBeijing2025ShouldReturnCorrectLimits(): void
    {
        $limits = $this->configProvider->getContributionLimits('beijing', InsuranceType::Pension, 2025);

        $expectedLimits = [
            'min_base' => 4800.0,
            'max_base' => 35000.0,
        ];

        $this->assertEquals($expectedLimits, $limits);
    }

    public function testGetContributionLimitsWithShenzhenHousingFundShouldReturnCorrectLimits(): void
    {
        $limits = $this->configProvider->getContributionLimits('shenzhen', InsuranceType::HousingFund, 2025);

        $expectedLimits = [
            'min_base' => 2000.0,
            'max_base' => 27000.0,
        ];

        $this->assertEquals($expectedLimits, $limits);
    }

    public function testGetContributionLimitsWithUnsupportedYearShouldReturnDefaultYearLimits(): void
    {
        $limits = $this->configProvider->getContributionLimits('beijing', InsuranceType::Pension, 2023);

        // 应该返回2025年的配置作为默认值
        $expectedLimits = [
            'min_base' => 4800.0,
            'max_base' => 35000.0,
        ];

        $this->assertEquals($expectedLimits, $limits);
    }

    public function testGetContributionLimitsWithUnsupportedRegionShouldReturnDefaultLimits(): void
    {
        $limits = $this->configProvider->getContributionLimits('unknown', InsuranceType::Medical, 2025);

        $expectedLimits = [
            'min_base' => 3000.0,
            'max_base' => 30000.0,
        ];

        $this->assertEquals($expectedLimits, $limits);
    }

    public function testGetSupportedRegionsShouldReturnAllConfiguredRegions(): void
    {
        $regions = $this->configProvider->getSupportedRegions();

        $expectedRegions = ['default', 'beijing', 'shanghai', 'guangzhou', 'shenzhen'];

        $this->assertEquals($expectedRegions, $regions);
    }

    public function testIsRegionSupportedWithValidRegionShouldReturnTrue(): void
    {
        $this->assertTrue($this->configProvider->isRegionSupported('beijing'));
        $this->assertTrue($this->configProvider->isRegionSupported('shanghai'));
        $this->assertTrue($this->configProvider->isRegionSupported('guangzhou'));
        $this->assertTrue($this->configProvider->isRegionSupported('shenzhen'));
        $this->assertTrue($this->configProvider->isRegionSupported('default'));
    }

    public function testIsRegionSupportedWithInvalidRegionShouldReturnFalse(): void
    {
        $this->assertFalse($this->configProvider->isRegionSupported('unknown'));
        $this->assertFalse($this->configProvider->isRegionSupported('hangzhou'));
        $this->assertFalse($this->configProvider->isRegionSupported(''));
    }

    public function testGetDefaultConfigWithBeijingShouldReturnBeijingConfig(): void
    {
        $config = $this->configProvider->getDefaultConfig('beijing');

        $this->assertEquals('北京市', $config['name']);
        $this->assertArrayHasKey('rates', $config);
        $this->assertArrayHasKey('limits', $config);
    }

    public function testGetDefaultConfigWithUnsupportedRegionShouldReturnDefaultConfig(): void
    {
        $config = $this->configProvider->getDefaultConfig('unknown');

        $this->assertEquals('全国通用', $config['name']);
        $this->assertArrayHasKey('rates', $config);
        $this->assertArrayHasKey('limits', $config);
    }

    public function testGuangzhouSpecialRatesShouldBeDifferentFromDefault(): void
    {
        $guangzhouPensionRates = $this->configProvider->getInsuranceRates('guangzhou', InsuranceType::Pension);
        $defaultPensionRates = $this->configProvider->getInsuranceRates('default', InsuranceType::Pension);

        // 广州养老保险企业缴费率为14%，与默认的20%不同
        $this->assertNotEquals($defaultPensionRates['employer_rate'], $guangzhouPensionRates['employer_rate']);
        $this->assertEquals(0.14, $guangzhouPensionRates['employer_rate']);
    }

    public function testShenzhenWorkInjuryRateShouldBeLowest(): void
    {
        $shenzhenRate = $this->configProvider->getInsuranceRates('shenzhen', InsuranceType::WorkInjury);

        // 深圳工伤保险率应该是最低的
        $this->assertEquals(0.0014, $shenzhenRate['employer_rate']);
        $this->assertEquals(0.0, $shenzhenRate['employee_rate']);
    }

    public function testAllRegionsHousingFundEmployeeRatesShouldMatch(): void
    {
        $regions = ['beijing', 'shanghai', 'guangzhou', 'shenzhen', 'default'];

        foreach ($regions as $region) {
            $rates = $this->configProvider->getInsuranceRates($region, InsuranceType::HousingFund);

            // 除了上海（7%）和深圳（13%），其他地区公积金个人缴费率都应该相等
            if ('shanghai' === $region) {
                $this->assertEquals(0.07, $rates['employee_rate'], '上海公积金个人缴费率应为7%');
            } elseif ('shenzhen' === $region) {
                $this->assertEquals(0.13, $rates['employee_rate'], '深圳公积金个人缴费率应为13%');
            } else {
                $this->assertEquals(0.12, $rates['employee_rate'], "{$region}公积金个人缴费率应为12%");
            }
        }
    }

    public function testContributionLimitsConsistencyAcrossInsuranceTypes(): void
    {
        // 测试北京地区养老保险和医疗保险的缴费基数上下限应该一致
        $pensionLimits = $this->configProvider->getContributionLimits('beijing', InsuranceType::Pension, 2025);
        $medicalLimits = $this->configProvider->getContributionLimits('beijing', InsuranceType::Medical, 2025);

        $this->assertEquals($pensionLimits['min_base'], $medicalLimits['min_base']);
        $this->assertEquals($pensionLimits['max_base'], $medicalLimits['max_base']);
    }

    public function testDefaultConfigStructureValidation(): void
    {
        $config = $this->configProvider->getDefaultConfig('default');

        // 验证配置结构的完整性
        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('rates', $config);
        $this->assertArrayHasKey('limits', $config);

        // 验证所有保险类型都有配置
        $expectedInsuranceTypes = ['pension', 'medical', 'unemployment', 'work_injury', 'maternity', 'housing_fund'];

        foreach ($expectedInsuranceTypes as $type) {
            $this->assertIsArray($config['rates']);
            $this->assertArrayHasKey($type, $config['rates']);

            $this->assertIsArray($config['limits']);
            $this->assertArrayHasKey(2025, $config['limits']);
            $this->assertIsArray($config['limits'][2025]);
            $this->assertArrayHasKey($type, $config['limits'][2025]);

            $this->assertIsArray($config['rates'][$type]);
            $this->assertArrayHasKey('employer_rate', $config['rates'][$type]);
            $this->assertArrayHasKey('employee_rate', $config['rates'][$type]);

            $this->assertIsArray($config['limits'][2025][$type]);
            $this->assertArrayHasKey('min_base', $config['limits'][2025][$type]);
            $this->assertArrayHasKey('max_base', $config['limits'][2025][$type]);
        }
    }
}
