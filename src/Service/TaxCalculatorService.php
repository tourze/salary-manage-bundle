<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Entity\Deduction;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\TaxBracket;
use Tourze\SalaryManageBundle\Entity\TaxResult;
use Tourze\SalaryManageBundle\Exception\TaxCalculationException;

/**
 * 个人所得税计算服务 - 实现累计预扣法
 * 符合2025年中国个人所得税法规定
 */
class TaxCalculatorService implements TaxCalculatorInterface
{
    public function __construct(
        private readonly TaxBracketProvider $bracketProvider,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function calculate(Employee $employee, float $taxableIncome, array $context = []): TaxResult
    {
        try {
            // 获取累计数据并进行类型验证
            $currentPeriodValue = $context['current_period'] ?? 1;
            $currentPeriod = is_int($currentPeriodValue) ? $currentPeriodValue : (is_numeric($currentPeriodValue) ? (int) $currentPeriodValue : 1);

            $cumulativeIncomeValue = $context['cumulative_income'] ?? $taxableIncome;
            $cumulativeIncome = is_float($cumulativeIncomeValue) || is_int($cumulativeIncomeValue) ? (float) $cumulativeIncomeValue : (is_numeric($cumulativeIncomeValue) ? (float) $cumulativeIncomeValue : $taxableIncome);

            $cumulativeTaxPaidValue = $context['cumulative_tax_paid'] ?? 0;
            $cumulativeTaxPaid = is_numeric($cumulativeTaxPaidValue) ? (float) $cumulativeTaxPaidValue : 0.0;

            $deductionsValue = $context['deductions'] ?? [];
            /** @var array<int, Deduction> $deductions */
            $deductions = is_array($deductionsValue) ? $deductionsValue : [];

            // 验证输入数据
            $this->validateInputData($taxableIncome, $currentPeriod, $cumulativeIncome);

            // 计算累计应纳税额
            $cumulativeTaxableIncome = $this->calculateCumulativeTaxableIncome(
                $cumulativeIncome,
                $currentPeriod,
                $deductions
            );

            // 使用速算法计算累计应纳税额
            $cumulativeTax = $this->calculateCumulativeTax($cumulativeTaxableIncome);

            // 计算当期应缴税额
            $currentTaxAmount = max(0, $cumulativeTax - $cumulativeTaxPaid);

            // 计算税后收入
            $netIncome = $taxableIncome - $currentTaxAmount;

            // 构建税额计算详情
            $calculationDetails = [
                'current_period' => $currentPeriod,
                'cumulative_income' => $cumulativeIncome,
                'cumulative_taxable_income' => $cumulativeTaxableIncome,
                'cumulative_tax' => $cumulativeTax,
                'cumulative_tax_paid' => $cumulativeTaxPaid,
                'current_tax_amount' => $currentTaxAmount,
                'marginal_rate' => $this->getMarginalRate($cumulativeTaxableIncome),
                'basic_deduction' => $this->bracketProvider->getMonthlyBasicDeduction() * $currentPeriod,
                'total_special_deductions' => $this->getTotalDeductions($deductions),
            ];

            $periodValue = $context['period'] ?? null;
            $period = $periodValue instanceof PayrollPeriod ? $periodValue : (function () {
                $defaultPeriod = new PayrollPeriod();
                $defaultPeriod->setYear((int) date('Y'));
                $defaultPeriod->setMonth((int) date('n'));

                return $defaultPeriod;
            })();

            return new TaxResult(
                employee: $employee,
                period: $period,
                grossIncome: $taxableIncome,
                taxableIncome: $cumulativeTaxableIncome,
                taxAmount: $currentTaxAmount,
                netIncome: $netIncome,
                deductions: $deductions,
                taxCalculationDetails: $calculationDetails
            );
        } catch (\Exception $e) {
            throw new TaxCalculationException(sprintf('税务计算失败: %s', $e->getMessage()), 0, $e);
        }
    }

    /** @return array<int, TaxBracket> */
    public function getTaxBrackets(): array
    {
        return array_values($this->bracketProvider->getSalaryTaxBrackets());
    }

    public function validateComplianceRules(TaxResult $result): bool
    {
        // 验证税率表正确性
        if (!$this->bracketProvider->validateTaxBrackets()) {
            return false;
        }

        // 验证计算结果合理性
        if (!$result->isValid()) {
            return false;
        }

        // 验证专项附加扣除合规性
        foreach ($result->getDeductions() as $deduction) {
            if ($deduction->getAmount() > $deduction->getType()->getMonthlyLimit()) {
                return false;
            }
        }

        // 验证税负合理性（最高不超过45%）
        if ($result->getEffectiveTaxRate() > 0.45) {
            return false;
        }

        return true;
    }

    /**
     * 计算累计应税收入
     */
    /**
     * @param array<int, Deduction> $deductions
     */
    private function calculateCumulativeTaxableIncome(
        float $cumulativeIncome,
        int $currentPeriod,
        array $deductions,
    ): float {
        $basicDeduction = $this->bracketProvider->getMonthlyBasicDeduction() * $currentPeriod;
        $specialDeductions = $this->getTotalDeductions($deductions) * $currentPeriod;

        return max(0, $cumulativeIncome - $basicDeduction - $specialDeductions);
    }

    /**
     * 计算累计应纳税额（使用速算扣除数法）
     */
    private function calculateCumulativeTax(float $cumulativeTaxableIncome): float
    {
        if ($cumulativeTaxableIncome <= 0) {
            return 0;
        }

        $bracket = $this->bracketProvider->findApplicableBracket($cumulativeTaxableIncome);
        if (null === $bracket) {
            throw new TaxCalculationException('无法找到适用的税率档次');
        }

        return $bracket->calculateTax($cumulativeTaxableIncome);
    }

    /**
     * 获取边际税率
     */
    private function getMarginalRate(float $cumulativeTaxableIncome): float
    {
        $bracket = $this->bracketProvider->findApplicableBracket($cumulativeTaxableIncome);

        return $bracket?->getRate() ?? 0;
    }

    /**
     * 计算专项附加扣除总额
     *
     * 不考虑并发 - 纯数学计算，不涉及共享状态
     */
    /**
     * @param array<int, Deduction> $deductions
     */
    private function getTotalDeductions(array $deductions): float
    {
        return array_sum(array_map(
            fn (Deduction $deduction) => $deduction->getAmount(),
            $deductions
        ));
    }

    /**
     * 验证输入数据的有效性
     */
    private function validateInputData(float $taxableIncome, int $currentPeriod, float $cumulativeIncome): void
    {
        if ($taxableIncome < 0) {
            throw new TaxCalculationException('应税收入不能为负数');
        }

        if ($currentPeriod < 1 || $currentPeriod > 12) {
            throw new TaxCalculationException('当前期数必须在1-12之间');
        }

        if ($cumulativeIncome < $taxableIncome) {
            throw new TaxCalculationException('累计收入不能小于当期收入');
        }
    }
}
