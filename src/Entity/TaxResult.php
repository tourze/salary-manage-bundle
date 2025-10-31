<?php

namespace Tourze\SalaryManageBundle\Entity;

use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * 税务计算结果 - 个人所得税计算结果聚合对象
 * 包含所有税务计算的相关信息
 */
readonly class TaxResult
{
    /**
     * @param array<int, Deduction> $deductions 专项附加扣除项
     * @param array<string, mixed> $taxCalculationDetails 税额计算详情
     * @param array<string, mixed> $metadata 额外元数据
     */
    public function __construct(
        private Employee $employee,
        private PayrollPeriod $period,
        private float $grossIncome,          // 税前收入
        private float $taxableIncome,        // 应税收入
        private float $taxAmount,            // 应缴税额
        private float $netIncome,            // 税后收入
        private array $deductions = [],      // 专项附加扣除项
        private array $taxCalculationDetails = [], // 税额计算详情
        private array $metadata = [],         // 额外元数据
        private float $basicDeduction = 5000.0,    // 基础扣除额
        private float $additionalDeduction = 0.0,  // 专项附加扣除
        private float $taxableAmount = 0.0,        // 应纳税所得额
        private float $taxRate = 0.0,              // 适用税率
        private float $cumulativeTax = 0.0,        // 累计已缴税额
        private float $currentTax = 0.0,           // 本期应缴税额
        private float $cumulativeIncome = 0.0,     // 累计收入
        private float $cumulativeBasicDeduction = 0.0,     // 累计基本扣除
        private float $cumulativeSpecialDeduction = 0.0,   // 累计专项扣除
        private float $cumulativeAdditionalDeduction = 0.0, // 累计专项附加扣除
        private float $cumulativeTaxableAmount = 0.0,      // 累计应纳税所得额
        private float $cumulativeTaxAmount = 0.0,          // 累计应纳税额
    ) {
        if ($grossIncome < 0 || $taxableIncome < 0 || $taxAmount < 0) {
            throw new DataValidationException('收入和税额不能为负数');
        }

        if ($netIncome < 0) {
            throw new DataValidationException('税后收入不能为负数');
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

    public function getGrossIncome(): float
    {
        return $this->grossIncome;
    }

    public function getTaxableIncome(): float
    {
        return $this->taxableIncome;
    }

    public function getTaxAmount(): float
    {
        return $this->taxAmount;
    }

    public function getNetIncome(): float
    {
        return $this->netIncome;
    }

    /**
     * @return Deduction[]
     */
    public function getDeductions(): array
    {
        return $this->deductions;
    }

    /** @return array<string, mixed> */
    public function getTaxCalculationDetails(): array
    {
        return $this->taxCalculationDetails;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * 获取总专项附加扣除额
     */
    public function getTotalDeductions(): float
    {
        return array_sum(array_map(
            fn (Deduction $deduction) => $deduction->getAmount(),
            $this->deductions
        ));
    }

    /**
     * 获取有效税率
     */
    public function getEffectiveTaxRate(): float
    {
        if ($this->grossIncome <= 0) {
            return 0.0;
        }

        return $this->taxAmount / $this->grossIncome;
    }

    /**
     * 获取边际税率
     */
    public function getMarginalTaxRate(): float
    {
        $details = $this->taxCalculationDetails;
        $marginalRate = $details['marginal_rate'] ?? 0.0;

        return is_float($marginalRate) ? $marginalRate : 0.0;
    }

    /**
     * 验证计算结果是否合理
     */
    public function isValid(): bool
    {
        // 基本合理性检查
        $netCalculated = $this->grossIncome - $this->taxAmount;
        $netDifference = abs($netCalculated - $this->netIncome);

        // 允许0.01的浮点误差
        return $netDifference < 0.01;
    }

    public function getBasicDeduction(): float
    {
        return $this->basicDeduction;
    }

    public function getAdditionalDeduction(): float
    {
        return $this->additionalDeduction;
    }

    public function getTaxableAmount(): float
    {
        return $this->taxableAmount;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function getCumulativeTax(): float
    {
        return $this->cumulativeTax;
    }

    public function getCurrentTax(): float
    {
        return $this->currentTax;
    }

    public function getCumulativeIncome(): float
    {
        return $this->cumulativeIncome;
    }

    public function getCumulativeBasicDeduction(): float
    {
        return $this->cumulativeBasicDeduction;
    }

    public function getCumulativeSpecialDeduction(): float
    {
        return $this->cumulativeSpecialDeduction;
    }

    public function getCumulativeAdditionalDeduction(): float
    {
        return $this->cumulativeAdditionalDeduction;
    }

    public function getCumulativeTaxableAmount(): float
    {
        return $this->cumulativeTaxableAmount;
    }

    public function getCumulativeTaxAmount(): float
    {
        return $this->cumulativeTaxAmount;
    }
}
