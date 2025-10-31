<?php

namespace Tourze\SalaryManageBundle\Interface;

use Tourze\SalaryManageBundle\Entity\ApprovalRequest;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;

interface ApprovalWorkflowInterface
{
    /**
     * 提交审批请求
     * @param array<int, SalaryCalculation> $salaryCalculations
     * @param array<string, mixed> $options
     */
    public function submitForApproval(
        array $salaryCalculations,
        PayrollPeriod $period,
        Employee $submitter,
        array $options = [],
    ): ApprovalRequest;

    /**
     * 执行审批操作
     */
    public function approve(
        ApprovalRequest $request,
        Employee $approver,
        string $comments = '',
    ): bool;

    /**
     * 拒绝审批
     */
    public function reject(
        ApprovalRequest $request,
        Employee $approver,
        string $reason,
    ): bool;

    /**
     * 获取待审批列表
     */
    /** @return array<ApprovalRequest> */
    public function getPendingApprovals(Employee $approver): array;

    /**
     * 获取审批历史
     */
    /** @return array<string, mixed> */
    public function getApprovalHistory(ApprovalRequest $request): array;

    /**
     * 检查审批权限
     */
    public function canApprove(ApprovalRequest $request, Employee $approver): bool;

    /**
     * 获取审批流程配置
     */
    /** @return array<string, mixed> */
    public function getWorkflowConfig(): array;

    /**
     * 自动审批检查
     */
    public function checkAutoApproval(ApprovalRequest $request): bool;
}
