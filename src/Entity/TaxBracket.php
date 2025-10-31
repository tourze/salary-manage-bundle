<?php

namespace Tourze\SalaryManageBundle\Entity;

use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * 税率档次 - 个人所得税税率表项目
 * 不可变值对象，表示一个税率区间
 */
readonly class TaxBracket
{
    public function __construct(
        private int $level,
        private float $minIncome,
        private float $maxIncome,
        private float $rate,
        private float $quickDeduction,
    ) {
        if ($level < 1 || $level > 7) {
            throw new DataValidationException('税率档次必须在1-7之间');
        }

        if ($rate < 0 || $rate > 1) {
            throw new DataValidationException('税率必须在0-1之间');
        }

        if ($minIncome < 0 || $maxIncome < 0 || $quickDeduction < 0) {
            throw new DataValidationException('收入和速算扣除数不能为负数');
        }

        if (INF !== $maxIncome && $maxIncome <= $minIncome) {
            throw new DataValidationException('最高收入必须大于最低收入');
        }
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getMinIncome(): float
    {
        return $this->minIncome;
    }

    public function getMaxIncome(): float
    {
        return $this->maxIncome;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getQuickDeduction(): float
    {
        return $this->quickDeduction;
    }

    /**
     * 检查收入是否在此税率档次范围内
     */
    public function isApplicable(float $income): bool
    {
        return $income > $this->minIncome
               && (INF === $this->maxIncome || $income <= $this->maxIncome);
    }

    /**
     * 计算此档次的应纳税额
     */
    public function calculateTax(float $income): float
    {
        if (!$this->isApplicable($income)) {
            return 0.0;
        }

        return $income * $this->rate - $this->quickDeduction;
    }
}
