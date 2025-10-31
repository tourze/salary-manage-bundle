<?php

namespace Tourze\SalaryManageBundle\Exception;

/**
 * 数据验证异常
 * 当输入数据不符合业务规则时抛出
 */
class DataValidationException extends \InvalidArgumentException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取错误恢复建议
     */
    public function getRecoverySuggestion(): string
    {
        return match (true) {
            str_contains($this->getMessage(), '员工编号') => '请检查员工编号格式，确保符合系统要求',
            str_contains($this->getMessage(), '基本薪资') => '请检查基本薪资金额，确保为有效数值',
            str_contains($this->getMessage(), '日期') => '请检查日期格式，确保为有效的日期值',
            str_contains($this->getMessage(), '金额') => '请检查金额数值，确保为正数且格式正确',
            str_contains($this->getMessage(), '类型') => '请检查输入的类型值是否在允许的范围内',
            default => '请检查输入数据的格式和内容是否符合要求',
        };
    }
}
