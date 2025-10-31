<?php

namespace Tourze\SalaryManageBundle\Interface;

use Tourze\SalaryManageBundle\Enum\InsuranceType;

interface RegionalConfigProviderInterface
{
    /**
     * 获取地区缴费比例配置
     *
     * @return array<string, float>
     */
    public function getInsuranceRates(string $region, InsuranceType $insuranceType): array;

    /**
     * 获取地区缴费基数上下限
     *
     * @return array<string, float>
     */
    public function getContributionLimits(string $region, InsuranceType $insuranceType, int $year): array;

    /**
     * 获取支持的地区列表
     *
     * @return array<int, string>
     */
    public function getSupportedRegions(): array;

    /**
     * 检查地区是否受支持
     */
    public function isRegionSupported(string $region): bool;

    /**
     * 获取地区的默认配置
     *
     * @return array<string, mixed>
     */
    public function getDefaultConfig(string $region): array;
}
