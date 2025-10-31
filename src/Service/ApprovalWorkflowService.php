<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Entity\ApprovalRequest;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Enum\ApprovalStatus;
use Tourze\SalaryManageBundle\Exception\ApprovalWorkflowException;
use Tourze\SalaryManageBundle\Interface\ApprovalWorkflowInterface;

class ApprovalWorkflowService implements ApprovalWorkflowInterface
{
    public function __construct(
        /** @var array<string, mixed> */
        private array $workflowConfig = [],
    ) {
        $this->workflowConfig = array_merge([
            'auto_approve_threshold' => 10000.0,
            'required_approver_role' => 'HR_MANAGER',
            'max_approval_days' => 7,
            'enable_auto_approval' => false,
        ], $workflowConfig);
    }

    /**
     * @param array<int, SalaryCalculation> $salaryCalculations
     * @param array<string, mixed> $options
     */
    public function submitForApproval(
        array $salaryCalculations,
        PayrollPeriod $period,
        Employee $submitter,
        array $options = [],
    ): ApprovalRequest {
        if ([] === $salaryCalculations) {
            throw new ApprovalWorkflowException('审批请求不能为空');
        }

        $requestId = $this->generateRequestId($period, $submitter);

        $approvalRequest = new ApprovalRequest();
        $approvalRequest->setRequestId($requestId);
        $approvalRequest->setSalaryCalculations($salaryCalculations);
        $approvalRequest->setPeriod($period);
        $approvalRequest->setSubmitter($submitter);
        $approvalRequest->setStatus(ApprovalStatus::Pending);
        $approvalRequest->setSubmittedAt(new \DateTimeImmutable());

        // 设置元数据
        $metadata = array_merge([
            'submission_source' => 'system',
            'total_employees' => count($salaryCalculations),
        ], $options);
        $approvalRequest->setMetadata($metadata);

        // 检查是否可以自动审批
        if ($this->checkAutoApproval($approvalRequest)) {
            return $this->performAutoApproval($approvalRequest);
        }

        return $approvalRequest;
    }

    public function approve(
        ApprovalRequest $request,
        Employee $approver,
        string $comments = '',
    ): bool {
        if (!$this->canApprove($request, $approver)) {
            throw new ApprovalWorkflowException('当前用户无权审批此请求', ['request_id' => $request->getRequestId(), 'approver_id' => $approver->getId()]);
        }

        if (!$request->isPending()) {
            throw new ApprovalWorkflowException("请求状态为 {$request->getStatus()->getLabel()}，无法审批", ['current_status' => $request->getStatus()->value]);
        }

        // 执行审批逻辑
        $approvedRequest = $request->withApproval($approver, $comments);

        // 这里可以添加审批后的业务逻辑，如发送通知等
        $this->onApprovalCompleted($approvedRequest);

        return true;
    }

    public function reject(
        ApprovalRequest $request,
        Employee $approver,
        string $reason,
    ): bool {
        if (!$this->canApprove($request, $approver)) {
            throw new ApprovalWorkflowException('当前用户无权拒绝此请求', ['request_id' => $request->getRequestId(), 'approver_id' => $approver->getId()]);
        }

        if (!$request->isPending()) {
            throw new ApprovalWorkflowException("请求状态为 {$request->getStatus()->getLabel()}，无法拒绝", ['current_status' => $request->getStatus()->value]);
        }

        if ('' === trim($reason)) {
            throw new ApprovalWorkflowException('拒绝理由不能为空');
        }

        // 执行拒绝逻辑
        $rejectedRequest = $request->withRejection($approver, $reason);

        // 这里可以添加拒绝后的业务逻辑，如发送通知等
        $this->onApprovalRejected($rejectedRequest);

        return true;
    }

    /** @return array<ApprovalRequest> */
    public function getPendingApprovals(Employee $approver): array
    {
        // 在实际实现中，这里会查询数据库获取待审批列表
        // 这里返回示例数据
        return [
            // ApprovalRequest instances would be returned here
        ];
    }

    /** @return array<string, mixed> */
    public function getApprovalHistory(ApprovalRequest $request): array
    {
        return $request->getApprovalHistory();
    }

    public function canApprove(ApprovalRequest $request, Employee $approver): bool
    {
        // 基础权限检查
        if (!$this->hasApprovalRole($approver)) {
            return false;
        }

        // 不能审批自己提交的请求
        if ($request->getSubmitter()->getId() === $approver->getId()) {
            return false;
        }

        // 检查金额权限
        if (!$this->hasAmountApprovalPermission($approver, $request->getTotalAmount())) {
            return false;
        }

        return true;
    }

    /** @return array<string, mixed> */
    public function getWorkflowConfig(): array
    {
        return $this->workflowConfig;
    }

    public function checkAutoApproval(ApprovalRequest $request): bool
    {
        if (!(bool) $this->workflowConfig['enable_auto_approval']) {
            return false;
        }

        // 检查总金额是否在自动审批阈值内
        if ($request->getTotalAmount() > $this->workflowConfig['auto_approve_threshold']) {
            return false;
        }

        // 检查员工数量（可选的自动审批规则）
        if ($request->getEmployeeCount() > 10) {
            return false;
        }

        return true;
    }

    private function generateRequestId(PayrollPeriod $period, Employee $submitter): string
    {
        return sprintf(
            'APPROVAL_%s_%s_%s',
            $period->getKey(),
            $submitter->getEmployeeNumber(),
            uniqid()
        );
    }

    private function performAutoApproval(ApprovalRequest $request): ApprovalRequest
    {
        $systemUser = $this->getSystemUser();

        return $request->withApproval(
            $systemUser,
            '系统自动审批: 符合自动审批条件'
        );
    }

    private function getSystemUser(): Employee
    {
        // 创建系统用户对象
        $employee = new Employee();
        $employee->setEmployeeNumber('SYSTEM');
        $employee->setName('系统自动审批');
        $employee->setDepartment('系统');
        $employee->setBaseSalary('0.00');
        $employee->setHireDate(new \DateTimeImmutable('2025-01-01'));

        return $employee;
    }

    private function hasApprovalRole(Employee $approver): bool
    {
        // 在实际实现中，这里会检查用户角色
        // 这里简单返回true作为示例
        return true;
    }

    private function hasAmountApprovalPermission(Employee $approver, float $totalAmount): bool
    {
        // 在实际实现中，这里会根据用户权限检查审批金额限制
        // 例如：不同级别的管理者有不同的审批额度限制

        // 这里简化处理，假设所有审批者都可以审批任何金额
        return true;
    }

    private function onApprovalCompleted(ApprovalRequest $approvedRequest): void
    {
        // 审批完成后的处理逻辑
        // 例如：发送通知、记录日志、触发后续流程等
    }

    private function onApprovalRejected(ApprovalRequest $rejectedRequest): void
    {
        // 审批拒绝后的处理逻辑
        // 例如：发送通知、记录日志、回退流程等
    }
}
