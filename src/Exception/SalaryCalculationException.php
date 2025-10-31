<?php

namespace Tourze\SalaryManageBundle\Exception;

/**
 * 薪资计算异常 (映射需求R1.9)
 * 当薪资计算过程中发生错误时抛出
 */
class SalaryCalculationException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取错误恢复建议 (映射需求R1.9)
     */
    public function getRecoverySuggestion(): string
    {
        return match (true) {
            str_contains($this->getMessage(), '不能为负数') => '请检查薪资项目配置，确保没有错误的扣除项目',
            str_contains($this->getMessage(), '结果不能为空') || str_contains($this->getMessage(), '计算结果不能为空') => '请确保员工至少有一个适用的薪资计算规则',
            str_contains($this->getMessage(), '计算规则') || str_contains($this->getMessage(), '规则') => '请检查相关计算规则的配置和输入参数',
            default => '请联系系统管理员检查薪资计算配置',
        };
    }
}
