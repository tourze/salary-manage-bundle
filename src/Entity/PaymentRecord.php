<?php

namespace Tourze\SalaryManageBundle\Entity;

use Tourze\SalaryManageBundle\Enum\PaymentMethod;
use Tourze\SalaryManageBundle\Enum\PaymentStatus;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

readonly class PaymentRecord
{
    public function __construct(
        private string $paymentId,
        private Employee $employee,
        private SalaryCalculation $salaryCalculation,
        private PayrollPeriod $period,
        private float $amount,
        private PaymentMethod $method,
        private PaymentStatus $status,
        private \DateTimeImmutable $processedAt,
        private ?string $bankTransactionId = null,
        private ?string $failureReason = null,
        /** @var array<string, mixed> */
        private array $metadata = [],
    ) {
        if ($amount <= 0) {
            throw new DataValidationException('发放金额必须大于0');
        }
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function getSalaryCalculation(): SalaryCalculation
    {
        return $this->salaryCalculation;
    }

    public function getPeriod(): PayrollPeriod
    {
        return $this->period;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getMethod(): PaymentMethod
    {
        return $this->method;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function getProcessedAt(): \DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function getBankTransactionId(): ?string
    {
        return $this->bankTransactionId;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isSuccessful(): bool
    {
        return PaymentStatus::Success === $this->status;
    }

    public function isFailed(): bool
    {
        return PaymentStatus::Failed === $this->status;
    }

    public function isPending(): bool
    {
        return PaymentStatus::Pending === $this->status;
    }

    /** @return array<string, mixed> */
    public function getDisplayInfo(): array
    {
        return [
            'payment_id' => $this->paymentId,
            'employee_name' => $this->employee->getName(),
            'amount' => number_format($this->amount, 2),
            'method' => $this->method->getLabel(),
            'status' => $this->status->getLabel(),
            'processed_at' => $this->processedAt->format('Y-m-d H:i:s'),
            'bank_transaction_id' => $this->bankTransactionId,
            'failure_reason' => $this->failureReason,
        ];
    }

    public function withStatus(PaymentStatus $newStatus, ?string $failureReason = null): PaymentRecord
    {
        return new self(
            $this->paymentId,
            $this->employee,
            $this->salaryCalculation,
            $this->period,
            $this->amount,
            $this->method,
            $newStatus,
            $this->processedAt,
            $this->bankTransactionId,
            $failureReason ?? $this->failureReason,
            $this->metadata
        );
    }

    public function withBankTransactionId(string $bankTransactionId): PaymentRecord
    {
        return new self(
            $this->paymentId,
            $this->employee,
            $this->salaryCalculation,
            $this->period,
            $this->amount,
            $this->method,
            $this->status,
            $this->processedAt,
            $bankTransactionId,
            $this->failureReason,
            $this->metadata
        );
    }
}
