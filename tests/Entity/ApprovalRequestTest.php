<?php

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SalaryManageBundle\Entity\ApprovalRequest;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Enum\ApprovalStatus;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * 审批请求实体测试
 * 验收标准：测试审批请求的完整业务逻辑和状态转换
 * @internal
 */
#[CoversClass(ApprovalRequest::class)]
final class ApprovalRequestTest extends AbstractEntityTestCase
{
    private Employee $submitter;

    private Employee $approver;

    private PayrollPeriod $period;

    /** @var array<int, SalaryCalculation> */
    private array $salaryCalculations = [];

    private \DateTimeImmutable $submittedAt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->submittedAt = new \DateTimeImmutable();

        // 初始化测试对象
        $this->submitter = new Employee();
        $this->submitter->setEmployeeNumber('EMP001');
        $this->submitter->setName('张三');
        $this->submitter->setDepartment('财务部');
        $this->submitter->setBaseSalary('8000.00');
        $this->submitter->setHireDate(new \DateTimeImmutable('2024-01-01'));

        $this->approver = new Employee();
        $this->approver->setEmployeeNumber('HR001');
        $this->approver->setName('李四');
        $this->approver->setDepartment('人事部');
        $this->approver->setBaseSalary('12000.00');
        $this->approver->setHireDate(new \DateTimeImmutable('2023-01-01'));

        $this->period = new PayrollPeriod();
        $this->period->setYear(2025);
        $this->period->setMonth(1);
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        // ApprovalRequest has complex relationships tested separately
        // Provide minimal writable properties for basic getter/setter tests
        return [
            ['requestId', 'REQ_001'],
            ['status', ApprovalStatus::Pending],
            ['submittedAt', new \DateTimeImmutable('2025-01-15')],
        ];
    }

    protected function createEntity(): ApprovalRequest
    {
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

        $approvalRequest = new ApprovalRequest();
        $approvalRequest->setRequestId('TEST_APPROVAL');
        $approvalRequest->setSalaryCalculations($this->salaryCalculations);
        $approvalRequest->setPeriod($this->period);
        $approvalRequest->setSubmitter($this->submitter);
        $approvalRequest->setSubmittedAt($this->submittedAt);

        return $approvalRequest;
    }

    public function testConstructWithValidDataShouldCreateInstance(): void
    {
        $request = $this->createEntity();

        $this->assertEquals('TEST_APPROVAL', $request->getRequestId());
        $this->assertEquals($this->salaryCalculations, $request->getSalaryCalculations());
        $this->assertSame($this->period, $request->getPeriod());
        $this->assertSame($this->submitter, $request->getSubmitter());
        $this->assertEquals(ApprovalStatus::Pending, $request->getStatus());
        $this->assertEquals($this->submittedAt, $request->getSubmittedAt());
        $this->assertIsArray($request->getMetadata());
    }

    public function testWithApprovalShouldCreateNewApprovedInstance(): void
    {
        $originalRequest = $this->createEntity();
        $comments = 'Approved by HR';

        $approvedRequest = $originalRequest->withApproval($this->approver, $comments);

        $this->assertNotSame($originalRequest, $approvedRequest);
        $this->assertEquals(ApprovalStatus::Approved, $approvedRequest->getStatus());
        $this->assertSame($this->approver, $approvedRequest->getApprover());
        $this->assertNotNull($approvedRequest->getApprovedAt());

        // 验证原实例未变更
        $this->assertEquals(ApprovalStatus::Pending, $originalRequest->getStatus());
        $this->assertNull($originalRequest->getApprover());

        // 验证审批历史
        $history = $approvedRequest->getApprovalHistory();
        $this->assertCount(1, $history);
        $latestEntry = end($history);
        $this->assertIsArray($latestEntry);
        $this->assertEquals('approved', $latestEntry['action']);
        $this->assertEquals($this->approver->getId(), $latestEntry['approver_id']);
        $this->assertEquals($this->approver->getName(), $latestEntry['approver_name']);
        $this->assertEquals($comments, $latestEntry['comments']);
    }

    public function testWithRejectionShouldCreateNewRejectedInstance(): void
    {
        $originalRequest = $this->createEntity();
        $reason = 'Missing documentation';

        $rejectedRequest = $originalRequest->withRejection($this->approver, $reason);

        $this->assertNotSame($originalRequest, $rejectedRequest);
        $this->assertEquals(ApprovalStatus::Rejected, $rejectedRequest->getStatus());
        $this->assertSame($this->approver, $rejectedRequest->getApprover());
        $this->assertEquals($reason, $rejectedRequest->getRejectionReason());

        // 验证原实例未变更
        $this->assertEquals(ApprovalStatus::Pending, $originalRequest->getStatus());
        $this->assertNull($originalRequest->getApprover());

        // 验证审批历史
        $history = $rejectedRequest->getApprovalHistory();
        $this->assertCount(1, $history);
        $latestEntry = end($history);
        $this->assertIsArray($latestEntry);
        $this->assertEquals('rejected', $latestEntry['action']);
        $this->assertEquals($this->approver->getId(), $latestEntry['approver_id']);
        $this->assertEquals($this->approver->getName(), $latestEntry['approver_name']);
        $this->assertEquals($reason, $latestEntry['reason']);
    }
}
