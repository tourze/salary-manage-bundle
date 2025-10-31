<?php

namespace Tourze\SalaryManageBundle\Exception;

class ApprovalWorkflowException extends \Exception
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
            str_contains($this->message, '审批请求不能为空') => '请至少选择一个薪资计算记录进行审批',
            str_contains($this->message, '无权审批') => '请联系系统管理员分配相应的审批权限',
            str_contains($this->message, '无法审批') => '只有待审批状态的请求可以进行审批操作',
            str_contains($this->message, '无法拒绝') => '只有待审批状态的请求可以进行拒绝操作',
            str_contains($this->message, '拒绝理由不能为空') => '请提供详细的拒绝理由',
            default => '请检查审批请求的状态和权限设置',
        };
    }
}
