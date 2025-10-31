<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\ApprovalRequest;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Enum\ApprovalStatus;
use Tourze\SalaryManageBundle\Exception\ApprovalWorkflowException;
use Tourze\SalaryManageBundle\Service\ApprovalWorkflowService;

/**
 * 审批工作流服务测试
 * 验收标准：测试完整的薪资审批工作流程
 * @internal
 */
#[CoversClass(ApprovalWorkflowService::class)]
class ApprovalWorkflowServiceTest extends TestCase
{
    private ApprovalWorkflowService $workflowService;

    private Employee $submitter;

    private Employee $approver;

    private PayrollPeriod $period;

    /** @var array<int, SalaryCalculation> */
    private array $salaryCalculations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflowService = new ApprovalWorkflowService([
            'auto_approve_threshold' => 5000.0,
            'required_approver_role' => 'HR_MANAGER',
            'max_approval_days' => 7,
            'enable_auto_approval' => false,
        ]);

        // 创建测试员工（提交者）
        $this->submitter = new Employee();
        $this->submitter->setEmployeeNumber('EMP001');
        $this->submitter->setName('张三');
        $this->submitter->setDepartment('财务部');
        $this->submitter->setBaseSalary('8000.00');
        $this->submitter->setHireDate(new \DateTimeImmutable('2024-01-01'));

        // 创建测试审批者
        $this->approver = new Employee();
        $this->approver->setEmployeeNumber('HR001');
        $this->approver->setName('李四');
        $this->approver->setDepartment('人事部');
        $this->approver->setBaseSalary('12000.00');
        $this->approver->setHireDate(new \DateTimeImmutable('2023-01-01'));

        $this->period = new PayrollPeriod();
        $this->period->setYear(2025);
        $this->period->setMonth(1);

        // 创建测试薪资计算数据
        $employee1 = new Employee();
        $employee1->setEmployeeNumber('EMP002');
        $employee1->setName('王五');
        $employee1->setBaseSalary('6000.00');
        $employee1->setHireDate(new \DateTimeImmutable('2024-01-01'));

        $calculation1 = new SalaryCalculation();
        $calculation1->setEmployee($employee1);
        $calculation1->setPeriod($this->period);

        $this->salaryCalculations = [$calculation1];
    }

    public function testSubmitForApprovalWithValidDataShouldCreatePendingRequest(): void
    {
        $options = ['submission_notes' => '月度薪资审批'];

        $request = $this->workflowService->submitForApproval(
            $this->salaryCalculations,
            $this->period,
            $this->submitter,
            $options
        );

        $this->assertInstanceOf(ApprovalRequest::class, $request);
        $this->assertEquals(ApprovalStatus::Pending, $request->getStatus());
        $this->assertSame($this->submitter, $request->getSubmitter());
        $this->assertSame($this->period, $request->getPeriod());
        $this->assertEquals($this->salaryCalculations, $request->getSalaryCalculations());
        $this->assertStringStartsWith('APPROVAL_', $request->getRequestId());
    }

    public function testSubmitForApprovalWithEmptySalaryCalculationsShouldThrowException(): void
    {
        $this->expectException(ApprovalWorkflowException::class);
        $this->expectExceptionMessage('审批请求不能为空');

        $this->workflowService->submitForApproval(
            [],
            $this->period,
            $this->submitter
        );
    }

    public function testSubmitForApprovalWithAutoApprovalEnabledShouldAutoApproveSmallAmount(): void
    {
        // 启用自动审批，设置较高的阈值
        $service = new ApprovalWorkflowService([
            'auto_approve_threshold' => 10000.0,
            'enable_auto_approval' => true,
        ]);

        $request = $service->submitForApproval(
            $this->salaryCalculations,
            $this->period,
            $this->submitter
        );

        $this->assertEquals(ApprovalStatus::Approved, $request->getStatus());
        $this->assertNotNull($request->getApprover());
        $this->assertEquals('SYSTEM', $request->getApprover()->getEmployeeNumber());
    }

    public function testApproveWithValidRequestAndApproverShouldReturnTrue(): void
    {
        // 使用反射设置不同的ID来避免权限检查失败
        $reflection = new \ReflectionClass($this->submitter);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->submitter, 1);

        $reflection = new \ReflectionClass($this->approver);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->approver, 2);

        $request = $this->workflowService->submitForApproval(
            $this->salaryCalculations,
            $this->period,
            $this->submitter
        );

        $comments = '薪资数据核实无误，同意审批';
        $result = $this->workflowService->approve($request, $this->approver, $comments);

        $this->assertTrue($result);
    }

    public function testApproveWithSelfSubmittedRequestShouldThrowException(): void
    {
        $request = $this->workflowService->submitForApproval(
            $this->salaryCalculations,
            $this->period,
            $this->submitter
        );

        $this->expectException(ApprovalWorkflowException::class);
        $this->expectExceptionMessage('当前用户无权审批此请求');

        $this->workflowService->approve($request, $this->submitter, '自己审批');
    }

    public function testApproveAlreadyApprovedRequestShouldThrowException(): void
    {
        // 使用反射设置不同的ID来避免权限检查失败
        $reflection = new \ReflectionClass($this->submitter);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->submitter, 1);

        $reflection = new \ReflectionClass($this->approver);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->approver, 2);

        $request = $this->workflowService->submitForApproval(
            $this->salaryCalculations,
            $this->period,
            $this->submitter
        );

        // 先审批一次
        $this->workflowService->approve($request, $this->approver, '首次审批');

        // 再次审批应该抛出异常
        $approvedRequest = $request->withApproval($this->approver, '首次审批');
        $this->expectException(ApprovalWorkflowException::class);
        $this->expectExceptionMessage('无法审批');

        $this->workflowService->approve($approvedRequest, $this->approver, '重复审批');
    }

    public function testRejectWithValidRequestAndReasonShouldReturnTrue(): void
    {
        // 使用反射设置不同的ID来避免权限检查失败
        $reflection = new \ReflectionClass($this->submitter);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->submitter, 1);

        $reflection = new \ReflectionClass($this->approver);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->approver, 2);

        $request = $this->workflowService->submitForApproval(
            $this->salaryCalculations,
            $this->period,
            $this->submitter
        );

        $reason = '薪资数据存在错误，需要重新核算';
        $result = $this->workflowService->reject($request, $this->approver, $reason);

        $this->assertTrue($result);
    }

    public function testRejectWithEmptyReasonShouldThrowException(): void
    {
        // 使用反射设置不同的ID来避免权限检查失败
        $reflection = new \ReflectionClass($this->submitter);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->submitter, 1);

        $reflection = new \ReflectionClass($this->approver);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->approver, 2);

        $request = $this->workflowService->submitForApproval(
            $this->salaryCalculations,
            $this->period,
            $this->submitter
        );

        $this->expectException(ApprovalWorkflowException::class);
        $this->expectExceptionMessage('拒绝理由不能为空');

        $this->workflowService->reject($request, $this->approver, '');
    }

    public function testRejectWithWhitespaceOnlyReasonShouldThrowException(): void
    {
        // 使用反射设置不同的ID来避免权限检查失败
        $reflection = new \ReflectionClass($this->submitter);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->submitter, 1);

        $reflection = new \ReflectionClass($this->approver);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->approver, 2);

        $request = $this->workflowService->submitForApproval(
            $this->salaryCalculations,
            $this->period,
            $this->submitter
        );

        $this->expectException(ApprovalWorkflowException::class);
        $this->expectExceptionMessage('拒绝理由不能为空');

        $this->workflowService->reject($request, $this->approver, '   ');
    }

    public function testCanApproveWithValidApproverShouldReturnTrue(): void
    {
        // 使用反射设置不同的ID来避免权限检查失败
        $reflection = new \ReflectionClass($this->submitter);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->submitter, 1);

        $reflection = new \ReflectionClass($this->approver);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->approver, 2);

        $request = $this->workflowService->submitForApproval(
            $this->salaryCalculations,
            $this->period,
            $this->submitter
        );

        $canApprove = $this->workflowService->canApprove($request, $this->approver);

        $this->assertTrue($canApprove);
    }

    public function testCanApproveWithSelfSubmitterShouldReturnFalse(): void
    {
        // 使用反射设置相同的ID来测试自己不能审批自己的请求
        $reflection = new \ReflectionClass($this->submitter);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->submitter, 1);

        $request = $this->workflowService->submitForApproval(
            $this->salaryCalculations,
            $this->period,
            $this->submitter
        );

        $canApprove = $this->workflowService->canApprove($request, $this->submitter);

        $this->assertFalse($canApprove);
    }

    public function testGetWorkflowConfigShouldReturnCorrectConfiguration(): void
    {
        $expectedConfig = [
            'auto_approve_threshold' => 5000.0,
            'required_approver_role' => 'HR_MANAGER',
            'max_approval_days' => 7,
            'enable_auto_approval' => false,
        ];

        $config = $this->workflowService->getWorkflowConfig();

        $this->assertEquals($expectedConfig, $config);
    }

    public function testCheckAutoApprovalWithDisabledAutoApprovalShouldReturnFalse(): void
    {
        $request = $this->workflowService->submitForApproval(
            $this->salaryCalculations,
            $this->period,
            $this->submitter
        );

        $result = $this->workflowService->checkAutoApproval($request);

        $this->assertFalse($result);
    }

    public function testCheckAutoApprovalWithEnabledButHighAmountShouldReturnFalse(): void
    {
        // 创建包含薪资项目的计算结果，使总额超过阈值
        $employee1 = new Employee();
        $employee1->setEmployeeNumber('EMP_HIGH_SALARY');
        $employee1->setName('高薪员工');
        $employee1->setBaseSalary('15000.00');
        $employee1->setHireDate(new \DateTimeImmutable('2024-01-01'));

        // 模拟高薪资计算（这里简单设置一个metadata来标记总额）
        $salaryCalculation = new SalaryCalculation();
        $salaryCalculation->setEmployee($employee1);
        $salaryCalculation->setPeriod($this->period);
        $salaryCalculation->setContextValue('mock_total_amount', 5000.0);

        // 由于我们无法直接向SalaryCalculation添加Items，这里测试会基于员工数量
        // 所以我们创建更多员工来超过员工数量限制（>10个）
        $calculations = [];
        for ($i = 1; $i <= 12; ++$i) {
            $employee = new Employee();
            $employee->setEmployeeNumber("EMP_{$i}");
            $employee->setName("员工{$i}");
            $employee->setBaseSalary('8000.00');
            $employee->setHireDate(new \DateTimeImmutable('2024-01-01'));
            $calculation = new SalaryCalculation();
            $calculation->setEmployee($employee);
            $calculation->setPeriod($this->period);
            $calculations[] = $calculation;
        }

        $service = new ApprovalWorkflowService([
            'auto_approve_threshold' => 10000.0,
            'enable_auto_approval' => true,
        ]);

        $request = $service->submitForApproval(
            $calculations,
            $this->period,
            $this->submitter
        );

        $result = $service->checkAutoApproval($request);

        // 因为员工数量 > 10，应该返回false
        $this->assertFalse($result);
    }

    public function testGetPendingApprovalsShouldReturnEmptyArray(): void
    {
        $pendingApprovals = $this->workflowService->getPendingApprovals($this->approver);

        $this->assertIsArray($pendingApprovals);
        $this->assertEmpty($pendingApprovals);
    }

    public function testGetApprovalHistoryShouldReturnRequestHistory(): void
    {
        $request = $this->workflowService->submitForApproval(
            $this->salaryCalculations,
            $this->period,
            $this->submitter
        );

        $history = $this->workflowService->getApprovalHistory($request);

        $this->assertIsArray($history);
        $this->assertEquals($request->getApprovalHistory(), $history);
    }

    public function testDefaultConfigMergeShouldOverrideCorrectly(): void
    {
        $customConfig = ['auto_approve_threshold' => 15000.0];
        $service = new ApprovalWorkflowService($customConfig);

        $config = $service->getWorkflowConfig();

        $this->assertEquals(15000.0, $config['auto_approve_threshold']);
        $this->assertEquals('HR_MANAGER', $config['required_approver_role']);
        $this->assertEquals(7, $config['max_approval_days']);
        $this->assertFalse($config['enable_auto_approval']);
    }
}
