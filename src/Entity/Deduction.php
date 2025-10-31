<?php

namespace Tourze\SalaryManageBundle\Entity;

use Tourze\SalaryManageBundle\Enum\DeductionType;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * 专项附加扣除项 - 个人所得税专项附加扣除
 * 不可变值对象，支持6项专项附加扣除
 */
readonly class Deduction
{
    public function __construct(
        private DeductionType $type,
        private float $amount,
        private string $description = '',
        /** @var array<string, mixed> */
        private array $metadata = [],
    ) {
        if ($amount < 0) {
            throw new DataValidationException('扣除金额不能为负数');
        }

        // 验证扣除金额是否符合法规限制
        $this->validateAmount($type, $amount);
    }

    public function getType(): DeductionType
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * 验证扣除金额是否符合2025年税法规定
     */
    private function validateAmount(DeductionType $type, float $amount): void
    {
        $maxAmount = match ($type) {
            DeductionType::ChildEducation => 2000, // 子女教育：每月2000元
            DeductionType::ContinuingEducation => 400, // 继续教育：每月400元
            DeductionType::SeriousIllness => 80000, // 大病医疗：每年最高80000元
            DeductionType::HousingLoan => 1000, // 住房贷款利息：每月1000元
            DeductionType::HousingRent => 1500, // 住房租金：每月最高1500元
            DeductionType::ElderCare => 3000, // 赡养老人：每月最高3000元
        };

        if ($amount > $maxAmount) {
            throw new DataValidationException(sprintf('扣除类型 %s 的金额 %.2f 超出法定上限 %.2f', $type->getLabel(), $amount, $maxAmount));
        }
    }
}
