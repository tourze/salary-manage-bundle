<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\AssertionFailedError;
use Tourze\SalaryManageBundle\Enum\InsuranceType;

/**
 * 测试用的区域配置提供器
 * 实现MockRegionalConfigProvider接口，用于测试中模拟配置数据
 */
class TestableRegionalConfigProvider implements MockRegionalConfigProvider
{
    /** @var array<string, array<string, array<string, float>>> */
    private array $insuranceRates = [];

    /** @var array<string, array<string, float>> */
    private array $contributionLimits = [];

    /** @var array<int, string> */
    private array $supportedRegions = ['default', 'beijing', 'shanghai'];

    /** @var array<string, int> */
    private array $callCounts = [];

    /** @var array<string, int> */
    private array $expectedCalls = [];

    /**
     * @param array<string, float> $rates
     */
    public function setInsuranceRates(array $rates): void
    {
        // Store rates for default region and insurance type
        $this->insuranceRates['default']['default'] = $rates;
    }

    /**
     * 兼容接口方法，接受三个参数但实际使用 setInsuranceRatesForRegion
     * @param string $region
     * @param InsuranceType $insuranceType
     * @param array<string, float> $rates
     */
    public function setInsuranceRatesForRegionCompat(string $region, InsuranceType $insuranceType, array $rates): void
    {
        $this->setInsuranceRatesForRegion($region, $insuranceType, $rates);
    }

    /**
     * @param string $region
     * @param InsuranceType $insuranceType
     * @param array<string, float> $rates
     */
    public function setInsuranceRatesForRegion(string $region, InsuranceType $insuranceType, array $rates): void
    {
        $this->insuranceRates[$region][$insuranceType->value] = $rates;
    }

    /**
     * @param array<string, float> $limits
     */
    public function setContributionLimits(array $limits): void
    {
        // Store limits for default region
        $this->contributionLimits['default'] = $limits;
    }

    /**
     * @param string $region
     * @param array<string, float> $limits
     */
    public function setContributionLimitsForRegion(string $region, array $limits): void
    {
        $this->contributionLimits[$region] = $limits;
    }

    /**
     * @param array<int, string> $regions
     */
    public function setSupportedRegions(array $regions): void
    {
        $this->supportedRegions = $regions;
    }

    public function expectCall(string $method, int $times = 1): void
    {
        $this->expectedCalls[$method] = $times;
        $this->callCounts[$method] = 0;
    }

    public function getInsuranceRates(string $region, InsuranceType $insuranceType): array
    {
        $this->callCounts['getInsuranceRates'] = ($this->callCounts['getInsuranceRates'] ?? 0) + 1;

        return $this->insuranceRates[$region][$insuranceType->value] ?? [];
    }

    public function getContributionLimits(string $region, InsuranceType $insuranceType, int $year): array
    {
        $this->callCounts['getContributionLimits'] = ($this->callCounts['getContributionLimits'] ?? 0) + 1;

        return $this->contributionLimits[$region] ?? [];
    }

    public function getSupportedRegions(): array
    {
        $this->callCounts['getSupportedRegions'] = ($this->callCounts['getSupportedRegions'] ?? 0) + 1;

        return $this->supportedRegions;
    }

    public function isRegionSupported(string $region): bool
    {
        $this->callCounts['isRegionSupported'] = ($this->callCounts['isRegionSupported'] ?? 0) + 1;

        return in_array($region, $this->supportedRegions, true);
    }

    public function getDefaultConfig(string $region): array
    {
        return [];
    }

    public function verifyExpectedCalls(): void
    {
        foreach ($this->expectedCalls as $method => $expectedCount) {
            $actualCount = $this->callCounts[$method] ?? 0;
            if ($actualCount !== $expectedCount) {
                throw new AssertionFailedError("Expected {$method} to be called {$expectedCount} times, but was called {$actualCount} times");
            }
        }
    }
}
