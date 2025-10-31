<?php

namespace Tourze\SalaryManageBundle\Entity;

use Tourze\SalaryManageBundle\Enum\InsuranceType;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * 缴费基数值对象
 * 管理社保公积金的缴费基数和上下限
 */
readonly class ContributionBase
{
    public function __construct(
        private InsuranceType $insuranceType,
        private float $baseAmount,          // 缴费基数
        private float $minAmount,           // 最低缴费基数
        private float $maxAmount,           // 最高缴费基数
        private string $region = 'default', // 地区标识
        private int $year = 2025,          // 适用年度
        /** @var array<string, mixed> */
        private array $metadata = [],        // 额外元数据
    ) {
        if ($baseAmount < 0 || $minAmount < 0 || $maxAmount < 0) {
            throw new DataValidationException('缴费基数不能为负数');
        }

        if ($maxAmount <= $minAmount) {
            throw new DataValidationException('最高缴费基数必须大于最低缴费基数');
        }

        if ($year < 2020 || $year > 2030) {
            throw new DataValidationException('年度必须在2020-2030之间');
        }
    }

    public function getInsuranceType(): InsuranceType
    {
        return $this->insuranceType;
    }

    public function getBaseAmount(): float
    {
        return $this->baseAmount;
    }

    public function getMinAmount(): float
    {
        return $this->minAmount;
    }

    public function getMaxAmount(): float
    {
        return $this->maxAmount;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * 获取实际缴费基数（应用上下限）
     */
    public function getActualBase(): float
    {
        return min($this->maxAmount, max($this->minAmount, $this->baseAmount));
    }

    /**
     * 检查基数是否需要调整
     */
    public function needsAdjustment(): bool
    {
        return $this->baseAmount < $this->minAmount || $this->baseAmount > $this->maxAmount;
    }

    /**
     * 获取调整后的缴费基数
     */
    public function getAdjustedBase(): ContributionBase
    {
        if (!$this->needsAdjustment()) {
            return $this;
        }

        $adjustedAmount = $this->getActualBase();

        return new self(
            $this->insuranceType,
            $adjustedAmount,
            $this->minAmount,
            $this->maxAmount,
            $this->region,
            $this->year,
            array_merge($this->metadata, ['adjusted' => true, 'original_base' => $this->baseAmount])
        );
    }
}
