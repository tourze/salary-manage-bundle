<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Entity\ContributionBase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SocialInsuranceResult;
use Tourze\SalaryManageBundle\Enum\InsuranceType;
use Tourze\SalaryManageBundle\Exception\InsuranceCalculationException;
use Tourze\SalaryManageBundle\Interface\RegionalConfigProviderInterface;
use Tourze\SalaryManageBundle\Interface\SocialInsuranceCalculatorInterface;

class SocialInsuranceCalculatorService implements SocialInsuranceCalculatorInterface
{
    public function __construct(
        private RegionalConfigProviderInterface $configProvider,
    ) {
    }

    public function calculateInsurance(
        Employee $employee,
        PayrollPeriod $period,
        InsuranceType $insuranceType,
        ContributionBase $contributionBase,
        string $region = 'default',
    ): SocialInsuranceResult {
        // 验证输入数据
        $this->validateInputs($employee, $period, $contributionBase, $region);

        // 获取地区缴费比例
        $rates = $this->getRegionalRates($region, $insuranceType);

        // 计算实际缴费基数
        $actualBase = $contributionBase->getActualBase();

        // 计算企业和个人缴费金额
        $employerAmount = $actualBase * $rates['employer_rate'];
        $employeeAmount = $actualBase * $rates['employee_rate'];

        return new SocialInsuranceResult(
            employee: $employee,
            period: $period,
            insuranceType: $insuranceType,
            contributionBase: $contributionBase,
            employerAmount: $employerAmount,
            employeeAmount: $employeeAmount,
            employerRate: $rates['employer_rate'],
            employeeRate: $rates['employee_rate'],
            region: $region,
            calculationDetails: [
                'actual_base' => $actualBase,
                'calculation_time' => date('Y-m-d H:i:s'),
                'region_rates' => $rates,
            ],
            metadata: [
                'calculator_version' => '2.1.0',
                'compliance_year' => 2025,
            ]
        );
    }

    /**
     * @param array<int, ContributionBase> $contributionBases
     * @return array<string, SocialInsuranceResult>
     */
    public function calculateAllInsurance(
        Employee $employee,
        PayrollPeriod $period,
        array $contributionBases,
        string $region = 'default',
    ): array {
        $results = [];

        foreach (InsuranceType::cases() as $insuranceType) {
            // 查找对应的缴费基数
            $contributionBase = $this->findContributionBase($contributionBases, $insuranceType);

            if (null === $contributionBase) {
                throw new InsuranceCalculationException("缺少{$insuranceType->getLabel()}的缴费基数配置", ['insurance_type' => $insuranceType->value]);
            }

            $results[$insuranceType->value] = $this->calculateInsurance(
                $employee,
                $period,
                $insuranceType,
                $contributionBase,
                $region
            );
        }

        return $results;
    }

    /** @return array<string, float> */
    public function getRegionalRates(string $region, InsuranceType $insuranceType): array
    {
        try {
            $rates = $this->configProvider->getInsuranceRates($region, $insuranceType);

            // 如果地区配置不存在，使用标准比例
            if ([] === $rates) {
                $rates = [
                    'employer_rate' => $insuranceType->getStandardEmployerRate(),
                    'employee_rate' => $insuranceType->getStandardEmployeeRate(),
                ];
            }

            return $rates;
        } catch (\Exception $e) {
            // 配置获取失败时使用标准比例
            return [
                'employer_rate' => $insuranceType->getStandardEmployerRate(),
                'employee_rate' => $insuranceType->getStandardEmployeeRate(),
            ];
        }
    }

    /** @return array<string, float> */
    public function getRegionalLimits(string $region, InsuranceType $insuranceType, int $year): array
    {
        try {
            return $this->configProvider->getContributionLimits($region, $insuranceType, $year);
        } catch (\Exception $e) {
            // 返回默认上下限
            return [
                'min_base' => 3000.0, // 全国最低基数参考
                'max_base' => 30000.0, // 全国最高基数参考
            ];
        }
    }

    public function validateContributionBase(
        ContributionBase $contributionBase,
        string $region,
    ): bool {
        $limits = $this->getRegionalLimits(
            $region,
            $contributionBase->getInsuranceType(),
            $contributionBase->getYear()
        );

        $actualBase = $contributionBase->getActualBase();

        return $actualBase >= $limits['min_base'] && $actualBase <= $limits['max_base'];
    }

    /**
     * 计算社保税前扣除总额
     *
     * 不考虑并发 - 纯数学计算，不涉及共享状态
     */
    /**
     * @param array<int, SocialInsuranceResult> $socialInsuranceResults
     */
    public function calculateTotalTaxDeduction(array $socialInsuranceResults): float
    {
        $totalDeduction = 0.0;

        foreach ($socialInsuranceResults as $result) {
            $totalDeduction += $result->getTaxDeductibleAmount();
        }

        return $totalDeduction;
    }

    /**
     * 验证输入参数
     */
    private function validateInputs(
        Employee $employee,
        PayrollPeriod $period,
        ContributionBase $contributionBase,
        string $region,
    ): void {
        if (!$this->configProvider->isRegionSupported($region)) {
            throw new InsuranceCalculationException("不支持的地区: {$region}", ['supported_regions' => $this->configProvider->getSupportedRegions()]);
        }

        if ($contributionBase->getYear() !== $period->getYear()) {
            throw new InsuranceCalculationException('缴费基数年度与工资期间年度不匹配', ['contribution_year' => $contributionBase->getYear(), 'period_year' => $period->getYear()]);
        }
    }

    /**
     * 查找对应保险类型的缴费基数
     */
    /**
     * @param array<int, ContributionBase> $contributionBases
     */
    private function findContributionBase(array $contributionBases, InsuranceType $insuranceType): ?ContributionBase
    {
        foreach ($contributionBases as $contributionBase) {
            if ($contributionBase->getInsuranceType() === $insuranceType) {
                return $contributionBase;
            }
        }

        return null;
    }
}
