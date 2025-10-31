<?php

namespace Tourze\SalaryManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\SalaryManageBundle\Enum\ApprovalStatus;
use Tourze\SalaryManageBundle\Exception\DataValidationException;
use Tourze\SalaryManageBundle\Repository\ApprovalRequestRepository;

/**
 * 审批请求实体 - 贫血模型设计
 * 只包含数据和getter/setter方法，无业务逻辑
 */
#[ORM\Entity(repositoryClass: ApprovalRequestRepository::class)]
#[ORM\Table(name: 'salary_approval_requests', options: ['comment' => '薪资审批请求表'])]
class ApprovalRequest implements \Stringable
{
    /** @phpstan-ignore property.unusedType */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '审批请求ID'])]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true, options: ['comment' => '请求唯一标识'])]
    #[Assert\NotBlank(message: '请求ID不能为空')]
    #[Assert\Length(max: 100, maxMessage: '请求ID不能超过100个字符')]
    private string $requestId;

    /**
     * @var array<int, SalaryCalculation>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '薪资计算记录'])]
    #[Assert\Type(type: 'array', message: '薪资计算记录必须是数组类型')]
    private array $salaryCalculations;

    #[ORM\ManyToOne(targetEntity: PayrollPeriod::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '薪资周期ID'])]
    #[Assert\NotNull(message: '薪资周期不能为空')]
    private PayrollPeriod $period;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '提交人ID'])]
    #[Assert\NotNull(message: '提交人不能为空')]
    private Employee $submitter;

    #[ORM\Column(type: Types::STRING, enumType: ApprovalStatus::class, options: ['comment' => '审批状态'])]
    #[Assert\NotNull(message: '审批状态不能为空')]
    #[Assert\Choice(callback: [ApprovalStatus::class, 'cases'], message: '审批状态必须是有效值')]
    private ApprovalStatus $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '提交时间'])]
    #[Assert\NotNull(message: '提交时间不能为空')]
    private \DateTimeImmutable $submittedAt;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '审批人ID'])]
    private ?Employee $approver = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '审批时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class, message: '审批时间必须是有效的日期时间')]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '审批评论'])]
    #[Assert\Length(max: 1000, maxMessage: '审批评论不能超过1000个字符')]
    private string $comments = '';

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '拒绝原因'])]
    #[Assert\Length(max: 1000, maxMessage: '拒绝原因不能超过1000个字符')]
    private string $rejectionReason = '';

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '审批历史记录'])]
    #[Assert\Type(type: 'array', message: '审批历史必须是数组类型')]
    private array $approvalHistory = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '元数据'])]
    #[Assert\Type(type: 'array', message: '元数据必须是数组类型')]
    private array $metadata = [];

    public function __construct()
    {
        $this->salaryCalculations = [];
        $this->status = ApprovalStatus::Pending;
        $this->submittedAt = new \DateTimeImmutable();
        $this->approvalHistory = [];
        $this->metadata = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function setRequestId(string $requestId): void
    {
        $this->requestId = $requestId;
    }

    /** @return array<int, SalaryCalculation> */
    public function getSalaryCalculations(): array
    {
        return $this->salaryCalculations;
    }

    /** @param array<int, SalaryCalculation> $salaryCalculations */
    public function setSalaryCalculations(array $salaryCalculations): void
    {
        $this->salaryCalculations = $salaryCalculations;
    }

    public function getPeriod(): PayrollPeriod
    {
        return $this->period;
    }

    public function setPeriod(PayrollPeriod $period): void
    {
        $this->period = $period;
    }

    public function getSubmitter(): Employee
    {
        return $this->submitter;
    }

    public function setSubmitter(Employee $submitter): void
    {
        $this->submitter = $submitter;
    }

    public function getStatus(): ApprovalStatus
    {
        return $this->status;
    }

    public function setStatus(ApprovalStatus $status): void
    {
        $this->status = $status;
    }

    public function getSubmittedAt(): \DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeImmutable $submittedAt): void
    {
        $this->submittedAt = $submittedAt;
    }

    public function getApprover(): ?Employee
    {
        return $this->approver;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    public function getRejectionReason(): string
    {
        return $this->rejectionReason;
    }

    /** @return array<string, mixed> */
    public function getApprovalHistory(): array
    {
        return $this->approvalHistory;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /** @param array<string, mixed> $metadata */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getTotalAmount(): float
    {
        return array_sum(array_map(
            fn (SalaryCalculation $calc) => $calc->getNetAmount(),
            $this->salaryCalculations
        ));
    }

    public function getEmployeeCount(): int
    {
        return count($this->salaryCalculations);
    }

    public function isPending(): bool
    {
        return ApprovalStatus::Pending === $this->status;
    }

    public function isApproved(): bool
    {
        return ApprovalStatus::Approved === $this->status;
    }

    public function isRejected(): bool
    {
        return ApprovalStatus::Rejected === $this->status;
    }

    public function withApproval(Employee $approver, string $comments = ''): ApprovalRequest
    {
        $history = $this->approvalHistory;
        $history['approval_' . time()] = [
            'action' => 'approved',
            'approver_id' => $approver->getId(),
            'approver_name' => $approver->getName(),
            'comments' => $comments,
            'timestamp' => new \DateTimeImmutable(),
        ];

        $approvedRequest = new self();
        $approvedRequest->setRequestId($this->requestId);
        $approvedRequest->setSalaryCalculations($this->salaryCalculations);
        $approvedRequest->setPeriod($this->period);
        $approvedRequest->setSubmitter($this->submitter);
        $approvedRequest->setStatus(ApprovalStatus::Approved);
        $approvedRequest->setSubmittedAt($this->submittedAt);
        $approvedRequest->approver = $approver;
        $approvedRequest->approvedAt = new \DateTimeImmutable();
        $approvedRequest->comments = $comments;
        $approvedRequest->rejectionReason = $this->rejectionReason;
        $approvedRequest->approvalHistory = $history;
        $approvedRequest->metadata = $this->metadata;

        return $approvedRequest;
    }

    public function withRejection(Employee $approver, string $reason): ApprovalRequest
    {
        $history = $this->approvalHistory;
        $history['rejection_' . time()] = [
            'action' => 'rejected',
            'approver_id' => $approver->getId(),
            'approver_name' => $approver->getName(),
            'reason' => $reason,
            'timestamp' => new \DateTimeImmutable(),
        ];

        $rejectedRequest = new self();
        $rejectedRequest->setRequestId($this->requestId);
        $rejectedRequest->setSalaryCalculations($this->salaryCalculations);
        $rejectedRequest->setPeriod($this->period);
        $rejectedRequest->setSubmitter($this->submitter);
        $rejectedRequest->setStatus(ApprovalStatus::Rejected);
        $rejectedRequest->setSubmittedAt($this->submittedAt);
        $rejectedRequest->approver = $approver;
        $rejectedRequest->approvedAt = new \DateTimeImmutable();
        $rejectedRequest->comments = $this->comments;
        $rejectedRequest->rejectionReason = $reason;
        $rejectedRequest->approvalHistory = $history;
        $rejectedRequest->metadata = $this->metadata;

        return $rejectedRequest;
    }

    /** @return array<string, mixed> */
    public function getDisplayInfo(): array
    {
        return [
            'request_id' => $this->requestId,
            'period' => $this->period->getKey(),
            'submitter' => $this->submitter->getName(),
            'employee_count' => $this->getEmployeeCount(),
            'total_amount' => number_format($this->getTotalAmount(), 2),
            'status' => $this->status->getLabel(),
            'submitted_at' => $this->submittedAt->format('Y-m-d H:i:s'),
            'approver' => $this->approver?->getName(),
            'approved_at' => $this->approvedAt?->format('Y-m-d H:i:s'),
            'comments' => $this->comments,
            'rejection_reason' => $this->rejectionReason,
        ];
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (%s - %s)',
            $this->requestId,
            $this->status->getLabel(),
            $this->period->getDisplayName()
        );
    }
}
