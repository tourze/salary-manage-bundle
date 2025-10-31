<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PaymentRecord;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Enum\PaymentMethod;
use Tourze\SalaryManageBundle\Enum\PaymentStatus;
use Tourze\SalaryManageBundle\Exception\PaymentProcessingException;
use Tourze\SalaryManageBundle\Interface\PaymentProcessorInterface;

class PaymentProcessorService implements PaymentProcessorInterface
{
    public function __construct(
        /** @var array<string, object> */
        private array $bankAdapters = [],
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function processPayment(
        Employee $employee,
        SalaryCalculation $salaryCalculation,
        PayrollPeriod $period,
        array $options = [],
    ): PaymentRecord {
        // 验证发放条件
        $this->validatePaymentConditions($salaryCalculation, $options);

        $methodValue = $options['method'] ?? 'bank_transfer';
        $method = is_string($methodValue) ? PaymentMethod::from($methodValue) : PaymentMethod::BankTransfer;
        $paymentId = $this->generatePaymentId($employee, $period);

        // 创建初始发放记录
        $paymentRecord = new PaymentRecord(
            paymentId: $paymentId,
            employee: $employee,
            salaryCalculation: $salaryCalculation,
            period: $period,
            amount: $salaryCalculation->getNetAmount(),
            method: $method,
            status: PaymentStatus::Pending,
            processedAt: new \DateTimeImmutable(),
            metadata: $options
        );

        try {
            // 执行实际支付处理
            return $this->executePayment($paymentRecord, $options);
        } catch (\Exception $e) {
            throw new PaymentProcessingException("薪资发放处理失败: {$e->getMessage()}", ['payment_id' => $paymentId, 'employee_id' => $employee->getId(), 'amount' => $salaryCalculation->getNetAmount(), 'method' => $method->value]);
        }
    }

    /**
     * @param array<int, SalaryCalculation> $salaryCalculations
     * @param array<string, mixed> $options
     */
    public function processBatchPayments(
        array $salaryCalculations,
        PayrollPeriod $period,
        array $options = [],
    ): array {
        if ([] === $salaryCalculations) {
            throw new PaymentProcessingException('批量发放列表不能为空');
        }

        $results = [];
        $batchId = $this->generateBatchId($period);
        $errors = [];

        foreach ($salaryCalculations as $salaryCalculation) {
            try {
                $employee = $salaryCalculation->getEmployee();
                $batchOptions = array_merge($options, ['batch_id' => $batchId]);

                $paymentRecord = $this->processPayment(
                    $employee,
                    $salaryCalculation,
                    $period,
                    $batchOptions
                );

                $results[] = $paymentRecord;
            } catch (PaymentProcessingException $e) {
                $errors[] = [
                    'employee' => $salaryCalculation->getEmployee()->getName(),
                    'error' => $e->getMessage(),
                    'context' => $e->getContext(),
                ];

                // 创建失败记录
                $methodValue = $options['method'] ?? 'bank_transfer';
                $method = is_string($methodValue) ? PaymentMethod::from($methodValue) : PaymentMethod::BankTransfer;

                $results[] = new PaymentRecord(
                    paymentId: $this->generatePaymentId($salaryCalculation->getEmployee(), $period),
                    employee: $salaryCalculation->getEmployee(),
                    salaryCalculation: $salaryCalculation,
                    period: $period,
                    amount: $salaryCalculation->getNetAmount(),
                    method: $method,
                    status: PaymentStatus::Failed,
                    processedAt: new \DateTimeImmutable(),
                    failureReason: $e->getMessage()
                );
            }
        }

        // 如果有错误但不是全部失败，记录批量处理结果
        if ([] !== $errors && [] !== array_filter($results, fn ($r) => $r->isSuccessful())) {
            // 部分成功的情况，记录详细信息
        }

        return $results;
    }

    /** @return array<string, string> */
    public function getSupportedMethods(): array
    {
        return [
            PaymentMethod::BankTransfer->value => PaymentMethod::BankTransfer->getLabel(),
            PaymentMethod::Cash->value => PaymentMethod::Cash->getLabel(),
            PaymentMethod::Payroll->value => PaymentMethod::Payroll->getLabel(),
            PaymentMethod::DigitalWallet->value => PaymentMethod::DigitalWallet->getLabel(),
        ];
    }

    public function addBankAdapter(string $bank, object $adapter): self
    {
        $this->bankAdapters[$bank] = $adapter;

        return $this;
    }

    public function getBankAdapter(string $bank): ?object
    {
        return $this->bankAdapters[$bank] ?? null;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function validatePaymentConditions(
        SalaryCalculation $salaryCalculation,
        array $options = [],
    ): bool {
        // 验证薪资计算结果
        if ($salaryCalculation->getNetAmount() <= 0) {
            throw new PaymentProcessingException('实发工资必须大于0', ['net_salary' => $salaryCalculation->getNetAmount()]);
        }

        // 验证发放方式
        $methodValue = $options['method'] ?? 'bank_transfer';
        $method = is_string($methodValue) ? $methodValue : 'bank_transfer';

        if (!array_key_exists($method, $this->getSupportedMethods())) {
            throw new PaymentProcessingException("不支持的发放方式: {$method}", ['supported_methods' => array_keys($this->getSupportedMethods())]);
        }

        // 验证银行信息（如果需要）
        $paymentMethod = PaymentMethod::from($method);
        if ($paymentMethod->requiresBankInfo() && !isset($options['bank_info'])) {
            throw new PaymentProcessingException("发放方式 {$paymentMethod->getLabel()} 需要提供银行信息");
        }

        return true;
    }

    public function cancelPayment(PaymentRecord $paymentRecord, string $reason): bool
    {
        if (!$paymentRecord->getStatus()->canCancel()) {
            throw new PaymentProcessingException("当前状态 {$paymentRecord->getStatus()->getLabel()} 不支持取消操作");
        }

        // 执行取消逻辑
        // 这里会调用相应的银行接口或第三方服务取消支付

        return true;
    }

    /** @return array<string, mixed> */
    public function getPaymentStatus(string $paymentId): array
    {
        // 查询支付状态的实现
        // 这里会调用数据库或外部服务查询状态

        return [
            'payment_id' => $paymentId,
            'status' => PaymentStatus::Success->value,
            'last_updated' => new \DateTimeImmutable(),
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function executePayment(
        PaymentRecord $paymentRecord,
        /** @var array<string, mixed> */
        array $options,
    ): PaymentRecord {
        $method = $paymentRecord->getMethod();

        // 更新状态为处理中
        $processingRecord = $paymentRecord->withStatus(PaymentStatus::Processing);

        switch ($method) {
            case PaymentMethod::BankTransfer:
            case PaymentMethod::Payroll:
                return $this->processBankTransfer($processingRecord, $options);

            case PaymentMethod::Cash:
                return $this->processCashPayment($processingRecord, $options);

            case PaymentMethod::DigitalWallet:
                return $this->processDigitalWallet($processingRecord, $options);

            default:
                throw new PaymentProcessingException("不支持的发放方式: {$method->value}");
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function processBankTransfer(
        PaymentRecord $paymentRecord,
        /** @var array<string, mixed> */
        array $options,
    ): PaymentRecord {
        // 模拟银行转账处理
        $bankInfo = $options['bank_info'] ?? [];

        // 这里会调用银行接口处理转账
        $bankTransactionId = 'TXN_' . uniqid();

        // 模拟处理时间
        if (isset($options['simulate_delay'])) {
            // 在测试环境中可能需要模拟处理时间
        }

        // 成功情况
        return $paymentRecord
            ->withStatus(PaymentStatus::Success)
            ->withBankTransactionId($bankTransactionId)
        ;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function processCashPayment(
        PaymentRecord $paymentRecord,
        /** @var array<string, mixed> */
        array $options,
    ): PaymentRecord {
        // 现金发放通常需要人工确认
        // 这里可能需要生成现金发放单或其他凭证

        return $paymentRecord->withStatus(PaymentStatus::Success);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function processDigitalWallet(
        PaymentRecord $paymentRecord,
        /** @var array<string, mixed> */
        array $options,
    ): PaymentRecord {
        // 数字钱包支付处理
        $walletInfo = $options['wallet_info'] ?? [];

        // 这里会调用数字钱包API
        $transactionId = 'WALLET_' . uniqid();

        return $paymentRecord
            ->withStatus(PaymentStatus::Success)
            ->withBankTransactionId($transactionId)
        ;
    }

    private function generatePaymentId(Employee $employee, PayrollPeriod $period): string
    {
        return sprintf(
            'PAY_%s_%s_%s',
            $period->getKey(),
            $employee->getEmployeeNumber(),
            uniqid()
        );
    }

    private function generateBatchId(PayrollPeriod $period): string
    {
        return sprintf(
            'BATCH_%s_%s',
            $period->getKey(),
            date('YmdHis')
        );
    }
}
