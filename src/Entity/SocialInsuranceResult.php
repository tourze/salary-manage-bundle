<?php

namespace Tourze\SalaryManageBundle\Entity;

use Tourze\SalaryManageBundle\Enum\InsuranceType;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * 社保公积金计算结果值对象
 * 包含企业和个人缴费金额及相关信息
 */
readonly class SocialInsuranceResult
{
    /**
     * @param array<string, mixed> $calculationDetails 计算详情
     * @param array<string, mixed> $metadata 额外元数据
     */
    public function __construct(
        private Employee $employee,
        private PayrollPeriod $period,
        private InsuranceType $insuranceType,
        private ContributionBase $contributionBase,
        private float $employerAmount,      // 企业缴费金额
        private float $employeeAmount,      // 个人缴费金额
        private float $employerRate,        // 企业缴费比例
        private float $employeeRate,        // 个人缴费比例
        private string $region = 'default', // 地区标识
        private array $calculationDetails = [], // 计算详情
        private array $metadata = [],        // 额外元数据
    ) {
        if ($employerAmount < 0 || $employeeAmount < 0) {
            throw new DataValidationException('缴费金额不能为负数');
        }

        if ($employerRate < 0 || $employeeRate < 0 || $employerRate > 1 || $employeeRate > 1) {
            throw new DataValidationException('缴费比例必须在0-1之间');
        }
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function getPeriod(): PayrollPeriod
    {
        return $this->period;
    }

    public function getInsuranceType(): InsuranceType
    {
        return $this->insuranceType;
    }

    public function getContributionBase(): ContributionBase
    {
        return $this->contributionBase;
    }

    public function getEmployerAmount(): float
    {
        return $this->employerAmount;
    }

    public function getEmployeeAmount(): float
    {
        return $this->employeeAmount;
    }

    public function getEmployerRate(): float
    {
        return $this->employerRate;
    }

    public function getEmployeeRate(): float
    {
        return $this->employeeRate;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    /** @return array<string, mixed> */
    public function getCalculationDetails(): array
    {
        return $this->calculationDetails;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * 获取总缴费金额（企业+个人）
     */
    public function getTotalAmount(): float
    {
        return $this->employerAmount + $this->employeeAmount;
    }

    /**
     * 获取企业缴费负担比例
     */
    public function getEmployerBurdenRatio(): float
    {
        $total = $this->getTotalAmount();

        return $total > 0 ? $this->employerAmount / $total : 0;
    }

    /**
     * 获取个人税前扣除金额
     */
    public function getTaxDeductibleAmount(): float
    {
        return $this->insuranceType->isTaxDeductible() ? $this->employeeAmount : 0;
    }

    /**
     * 验证计算结果的合理性
     */
    public function isValid(): bool
    {
        // 检查计算是否合理
        $expectedEmployerAmount = $this->contributionBase->getActualBase() * $this->employerRate;
        $expectedEmployeeAmount = $this->contributionBase->getActualBase() * $this->employeeRate;

        // 允许0.01的浮点误差
        $employerDiff = abs($expectedEmployerAmount - $this->employerAmount);
        $employeeDiff = abs($expectedEmployeeAmount - $this->employeeAmount);

        return $employerDiff < 0.01 && $employeeDiff < 0.01;
    }

    /**
     * 获取格式化的显示信息
     */
    /** @return array<string, mixed> */
    public function getDisplayInfo(): array
    {
        return [
            'insurance_type' => $this->insuranceType->getLabel(),
            'contribution_base' => number_format($this->contributionBase->getActualBase(), 2),
            'employer_amount' => number_format($this->employerAmount, 2),
            'employee_amount' => number_format($this->employeeAmount, 2),
            'total_amount' => number_format($this->getTotalAmount(), 2),
            'employer_rate' => sprintf('%.2f%%', $this->employerRate * 100),
            'employee_rate' => sprintf('%.2f%%', $this->employeeRate * 100),
            'region' => $this->region,
            'period' => $this->period->getKey(),
        ];
    }

    /**
     * 获取个人缴费总额
     */
    public function getTotalPersonalContribution(): float
    {
        return $this->employeeAmount;
    }

    /**
     * 获取企业缴费总额
     */
    public function getTotalCompanyContribution(): float
    {
        return $this->employerAmount;
    }

    /**
     * 获取养老保险缴费金额
     */
    public function getPensionContribution(): float
    {
        return InsuranceType::Pension === $this->insuranceType ? $this->getTotalAmount() : 0;
    }

    /**
     * 获取医疗保险缴费金额
     */
    public function getMedicalContribution(): float
    {
        return InsuranceType::Medical === $this->insuranceType ? $this->getTotalAmount() : 0;
    }

    /**
     * 获取失业保险缴费金额
     */
    public function getUnemploymentContribution(): float
    {
        return InsuranceType::Unemployment === $this->insuranceType ? $this->getTotalAmount() : 0;
    }

    /**
     * 获取工伤保险缴费金额
     */
    public function getWorkInjuryContribution(): float
    {
        return InsuranceType::WorkInjury === $this->insuranceType ? $this->getTotalAmount() : 0;
    }

    /**
     * 获取生育保险缴费金额
     */
    public function getMaternityContribution(): float
    {
        return InsuranceType::Maternity === $this->insuranceType ? $this->getTotalAmount() : 0;
    }

    /**
     * 获取住房公积金缴费金额
     */
    public function getHousingFundContribution(): float
    {
        return InsuranceType::HousingFund === $this->insuranceType ? $this->getTotalAmount() : 0;
    }
}
