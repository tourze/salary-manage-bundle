<?php

namespace Tourze\SalaryManageBundle\Interface;

use Tourze\SalaryManageBundle\Entity\ContributionBase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SocialInsuranceResult;
use Tourze\SalaryManageBundle\Enum\InsuranceType;

interface SocialInsuranceCalculatorInterface
{
    /**
     * 计算单项社保公积金
     */
    public function calculateInsurance(
        Employee $employee,
        PayrollPeriod $period,
        InsuranceType $insuranceType,
        ContributionBase $contributionBase,
        string $region = 'default',
    ): SocialInsuranceResult;

    /**
     * 计算五险一金全部项目
     *
     * @param array<int, ContributionBase> $contributionBases
     * @return array<string, SocialInsuranceResult>
     */
    public function calculateAllInsurance(
        Employee $employee,
        PayrollPeriod $period,
        array $contributionBases,
        string $region = 'default',
    ): array;

    /**
     * 获取地区缴费比例配置
     *
     * @return array<string, float>
     */
    public function getRegionalRates(string $region, InsuranceType $insuranceType): array;

    /**
     * 获取地区缴费基数上下限
     *
     * @return array<string, float>
     */
    public function getRegionalLimits(string $region, InsuranceType $insuranceType, int $year): array;

    /**
     * 验证缴费基数是否符合地区规定
     */
    public function validateContributionBase(
        ContributionBase $contributionBase,
        string $region,
    ): bool;

    /**
     * 计算税前扣除总额
     *
     * @param array<int, SocialInsuranceResult> $socialInsuranceResults
     */
    public function calculateTotalTaxDeduction(array $socialInsuranceResults): float;
}
