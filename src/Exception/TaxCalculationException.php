<?php

namespace Tourze\SalaryManageBundle\Exception;

/**
 * 税务计算异常 (映射需求R2.9)
 * 当税务计算过程中发生错误时抛出
 */
class TaxCalculationException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取错误恢复建议 (映射需求R2.9)
     */
    public function getRecoverySuggestion(): string
    {
        return match (true) {
            str_contains($this->getMessage(), '应税收入') && str_contains($this->getMessage(), '不能为负数') => '请检查收入数据计算，确保所有收入项目为正数',
            str_contains($this->getMessage(), '累计收入') && str_contains($this->getMessage(), '小于当期收入') => '请检查累计收入数据是否正确，累计收入应大于等于当期收入',
            str_contains($this->getMessage(), '期数') && str_contains($this->getMessage(), '1-12') => '请检查薪资期数设置，期数应该在1-12月之间',
            str_contains($this->getMessage(), '税率表') || str_contains($this->getMessage(), '税率档次') => '请检查税率表配置，确保覆盖所有收入区间',
            str_contains($this->getMessage(), '扣除') && (str_contains($this->getMessage(), '超过') || str_contains($this->getMessage(), '超限') || str_contains($this->getMessage(), '超出') || str_contains($this->getMessage(), '配置错误')) => '请检查专项附加扣除配置，确保金额不超过法定上限',
            default => '请联系系统管理员检查税务计算配置和相关参数',
        };
    }
}
