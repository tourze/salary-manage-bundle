<?php

namespace Tourze\SalaryManageBundle\Exception;

class InsuranceCalculationException extends \Exception
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        private array $context = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getRecoveryHint(): string
    {
        return match (true) {
            str_contains($this->message, '不支持的地区') => '请使用支持的地区代码，或联系管理员添加地区配置',
            str_contains($this->message, '缴费基数') => '请检查缴费基数设置，确保符合当地规定',
            str_contains($this->message, '缺少') => '请确保所有保险类型都有对应的缴费基数配置',
            str_contains($this->message, '年度') => '请确保缴费基数年度与工资期间年度一致',
            default => '请检查输入数据的完整性和有效性',
        };
    }
}
