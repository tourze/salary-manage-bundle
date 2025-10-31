<?php

namespace Tourze\SalaryManageBundle\Exception;

class PaymentProcessingException extends \Exception
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
            str_contains($this->message, '实发工资必须大于0') => '请检查薪资计算结果，确保实发工资为正数',
            str_contains($this->message, '不支持的发放方式') => '请选择支持的发放方式：银行转账、现金、代发工资或数字钱包',
            str_contains($this->message, '需要提供银行信息') => '请完善员工银行账户信息',
            str_contains($this->message, '批量发放列表不能为空') => '请至少选择一个员工进行发放',
            str_contains($this->message, '不支持取消操作') => '只有待处理或处理中的发放记录可以取消',
            default => '请检查发放参数和员工信息的完整性',
        };
    }
}
