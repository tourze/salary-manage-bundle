<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use Tourze\SalaryManageBundle\Enum\InsuranceType;
use Tourze\SalaryManageBundle\Interface\RegionalConfigProviderInterface;

/**
 * 测试辅助接口，扩展主接口并添加用于测试的setter方法
 */
interface MockRegionalConfigProvider extends RegionalConfigProviderInterface
{
    /**
     * @param array<string, float> $rates
     */
    public function setInsuranceRates(array $rates): void;

    /**
     * @param array<string, float> $limits
     */
    public function setContributionLimits(array $limits): void;

    /**
     * @param array<int, string> $regions
     */
    public function setSupportedRegions(array $regions): void;

    public function expectCall(string $method, int $times = 1): void;

    public function verifyExpectedCalls(): void;
}
