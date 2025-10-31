<?php

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PaymentRecord;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Enum\PaymentMethod;
use Tourze\SalaryManageBundle\Enum\PaymentStatus;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * 支付记录实体测试
 * 测试薪资支付记录的状态管理和显示逻辑
 * @internal
 */
#[CoversClass(PaymentRecord::class)]
final class PaymentRecordTest extends TestCase
{
    private Employee $employee;

    private SalaryCalculation $salaryCalculation;

    private PayrollPeriod $period;

    private \DateTimeImmutable $processedAt;

    protected function setUp(): void
    {
        // 创建测试员工
        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('EMP001');
        $this->employee->setName('张三');
        $this->employee->setDepartment('技术部');
        $this->employee->setBaseSalary('8000.00');
        $this->employee->setHireDate(new \DateTimeImmutable('2024-01-01'));

        $this->period = new PayrollPeriod();
        $this->period->setYear(2025);
        $this->period->setMonth(1);
        $this->salaryCalculation = new SalaryCalculation();
        $this->salaryCalculation->setEmployee($this->employee);
        $this->salaryCalculation->setPeriod($this->period);
        $this->processedAt = new \DateTimeImmutable('2025-01-15 10:30:00');
    }

    public function testConstructWithValidDataShouldCreateInstance(): void
    {
        $paymentId = 'PAY_202501_EMP001_001';
        $amount = 7500.0;
        $bankTransactionId = 'BANK_TXN_123456789';
        $metadata = ['payment_batch' => 'BATCH_001', 'priority' => 'normal'];

        $paymentRecord = new PaymentRecord(
            paymentId: $paymentId,
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: $amount,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Success,
            processedAt: $this->processedAt,
            bankTransactionId: $bankTransactionId,
            metadata: $metadata
        );

        $this->assertEquals($paymentId, $paymentRecord->getPaymentId());
        $this->assertSame($this->employee, $paymentRecord->getEmployee());
        $this->assertSame($this->salaryCalculation, $paymentRecord->getSalaryCalculation());
        $this->assertSame($this->period, $paymentRecord->getPeriod());
        $this->assertEquals($amount, $paymentRecord->getAmount());
        $this->assertEquals(PaymentMethod::BankTransfer, $paymentRecord->getMethod());
        $this->assertEquals(PaymentStatus::Success, $paymentRecord->getStatus());
        $this->assertEquals($this->processedAt, $paymentRecord->getProcessedAt());
        $this->assertEquals($bankTransactionId, $paymentRecord->getBankTransactionId());
        $this->assertNull($paymentRecord->getFailureReason());
        $this->assertEquals($metadata, $paymentRecord->getMetadata());
    }

    public function testConstructWithDefaultOptionalParametersShouldUseDefaults(): void
    {
        $paymentRecord = new PaymentRecord(
            paymentId: 'PAY_TEST',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 5000.0,
            method: PaymentMethod::Cash,
            status: PaymentStatus::Pending,
            processedAt: $this->processedAt
        );

        $this->assertNull($paymentRecord->getBankTransactionId());
        $this->assertNull($paymentRecord->getFailureReason());
        $this->assertEquals([], $paymentRecord->getMetadata());
    }

    public function testConstructWithZeroAmountShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('发放金额必须大于0');

        new PaymentRecord(
            paymentId: 'PAY_TEST',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 0.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Pending,
            processedAt: $this->processedAt
        );
    }

    public function testConstructWithNegativeAmountShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('发放金额必须大于0');

        new PaymentRecord(
            paymentId: 'PAY_TEST',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: -1000.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Pending,
            processedAt: $this->processedAt
        );
    }

    public function testIsSuccessfulWithSuccessStatusShouldReturnTrue(): void
    {
        $paymentRecord = new PaymentRecord(
            paymentId: 'PAY_TEST',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 5000.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Success,
            processedAt: $this->processedAt
        );

        $this->assertTrue($paymentRecord->isSuccessful());
        $this->assertFalse($paymentRecord->isFailed());
        $this->assertFalse($paymentRecord->isPending());
    }

    public function testIsFailedWithFailedStatusShouldReturnTrue(): void
    {
        $paymentRecord = new PaymentRecord(
            paymentId: 'PAY_TEST',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 5000.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Failed,
            processedAt: $this->processedAt,
            failureReason: '银行账户信息错误'
        );

        $this->assertTrue($paymentRecord->isFailed());
        $this->assertFalse($paymentRecord->isSuccessful());
        $this->assertFalse($paymentRecord->isPending());
        $this->assertEquals('银行账户信息错误', $paymentRecord->getFailureReason());
    }

    public function testIsPendingWithPendingStatusShouldReturnTrue(): void
    {
        $paymentRecord = new PaymentRecord(
            paymentId: 'PAY_TEST',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 5000.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Pending,
            processedAt: $this->processedAt
        );

        $this->assertTrue($paymentRecord->isPending());
        $this->assertFalse($paymentRecord->isSuccessful());
        $this->assertFalse($paymentRecord->isFailed());
    }

    public function testGetDisplayInfoShouldReturnFormattedData(): void
    {
        $paymentId = 'PAY_202501_EMP001_001';
        $amount = 7856.25;
        $bankTransactionId = 'BANK_TXN_987654321';

        $paymentRecord = new PaymentRecord(
            paymentId: $paymentId,
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: $amount,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Success,
            processedAt: $this->processedAt,
            bankTransactionId: $bankTransactionId
        );

        $displayInfo = $paymentRecord->getDisplayInfo();

        $this->assertEquals($paymentId, $displayInfo['payment_id']);
        $this->assertEquals($this->employee->getName(), $displayInfo['employee_name']);
        $this->assertEquals('7,856.25', $displayInfo['amount']);
        $this->assertEquals(PaymentMethod::BankTransfer->getLabel(), $displayInfo['method']);
        $this->assertEquals(PaymentStatus::Success->getLabel(), $displayInfo['status']);
        $this->assertEquals('2025-01-15 10:30:00', $displayInfo['processed_at']);
        $this->assertEquals($bankTransactionId, $displayInfo['bank_transaction_id']);
        $this->assertNull($displayInfo['failure_reason']);
    }

    public function testGetDisplayInfoWithFailureReasonShouldIncludeReason(): void
    {
        $failureReason = '账户余额不足';

        $paymentRecord = new PaymentRecord(
            paymentId: 'PAY_TEST',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 5000.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Failed,
            processedAt: $this->processedAt,
            failureReason: $failureReason
        );

        $displayInfo = $paymentRecord->getDisplayInfo();

        $this->assertEquals($failureReason, $displayInfo['failure_reason']);
        $this->assertEquals(PaymentStatus::Failed->getLabel(), $displayInfo['status']);
    }

    public function testWithStatusShouldCreateNewInstanceWithUpdatedStatus(): void
    {
        $originalRecord = new PaymentRecord(
            paymentId: 'PAY_TEST',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 5000.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Pending,
            processedAt: $this->processedAt
        );

        $failureReason = '网络连接超时';
        $updatedRecord = $originalRecord->withStatus(PaymentStatus::Failed, $failureReason);

        // 验证新实例的状态
        $this->assertEquals(PaymentStatus::Failed, $updatedRecord->getStatus());
        $this->assertEquals($failureReason, $updatedRecord->getFailureReason());

        // 验证原实例未变更
        $this->assertEquals(PaymentStatus::Pending, $originalRecord->getStatus());
        $this->assertNull($originalRecord->getFailureReason());

        // 验证其他属性保持不变
        $this->assertEquals($originalRecord->getPaymentId(), $updatedRecord->getPaymentId());
        $this->assertSame($originalRecord->getEmployee(), $updatedRecord->getEmployee());
        $this->assertEquals($originalRecord->getAmount(), $updatedRecord->getAmount());
    }

    public function testWithStatusFromFailedToSuccessShouldPreserveExistingFailureReason(): void
    {
        $originalRecord = new PaymentRecord(
            paymentId: 'PAY_TEST',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 5000.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Failed,
            processedAt: $this->processedAt,
            failureReason: '原有失败原因'
        );

        $successRecord = $originalRecord->withStatus(PaymentStatus::Success);

        $this->assertEquals(PaymentStatus::Success, $successRecord->getStatus());
        $this->assertEquals('原有失败原因', $successRecord->getFailureReason());
    }

    public function testWithBankTransactionIdShouldCreateNewInstanceWithTransactionId(): void
    {
        $originalRecord = new PaymentRecord(
            paymentId: 'PAY_TEST',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 5000.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Processing,
            processedAt: $this->processedAt
        );

        $bankTransactionId = 'BANK_TXN_NEW_123';
        $updatedRecord = $originalRecord->withBankTransactionId($bankTransactionId);

        // 验证新实例的银行交易ID
        $this->assertEquals($bankTransactionId, $updatedRecord->getBankTransactionId());

        // 验证原实例未变更
        $this->assertNull($originalRecord->getBankTransactionId());

        // 验证其他属性保持不变
        $this->assertEquals($originalRecord->getStatus(), $updatedRecord->getStatus());
        $this->assertEquals($originalRecord->getAmount(), $updatedRecord->getAmount());
    }

    public function testImmutabilityOfReadonlyClass(): void
    {
        $originalRecord = new PaymentRecord(
            paymentId: 'PAY_TEST',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 5000.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Pending,
            processedAt: $this->processedAt,
            metadata: ['original' => 'data']
        );

        $originalStatus = $originalRecord->getStatus();
        $originalTransactionId = $originalRecord->getBankTransactionId();
        $originalMetadata = $originalRecord->getMetadata();

        // 通过withStatus创建新实例
        $updatedRecord = $originalRecord->withStatus(PaymentStatus::Success);

        // 原实例应保持不变
        $this->assertEquals($originalStatus, $originalRecord->getStatus());
        $this->assertEquals($originalTransactionId, $originalRecord->getBankTransactionId());
        $this->assertEquals($originalMetadata, $originalRecord->getMetadata());

        // 新实例应有新状态
        $this->assertEquals(PaymentStatus::Success, $updatedRecord->getStatus());
    }

    public function testDifferentPaymentMethodsShouldMaintainMethodIdentity(): void
    {
        $bankTransferRecord = new PaymentRecord(
            paymentId: 'PAY_BANK',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 5000.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Success,
            processedAt: $this->processedAt
        );

        $cashRecord = new PaymentRecord(
            paymentId: 'PAY_CASH',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 3000.0,
            method: PaymentMethod::Cash,
            status: PaymentStatus::Success,
            processedAt: $this->processedAt
        );

        $this->assertEquals(PaymentMethod::BankTransfer, $bankTransferRecord->getMethod());
        $this->assertEquals(PaymentMethod::Cash, $cashRecord->getMethod());
        $this->assertNotEquals($bankTransferRecord->getMethod(), $cashRecord->getMethod());
    }

    public function testMetadataHandlingThroughStatusChanges(): void
    {
        $originalMetadata = [
            'payment_batch' => 'BATCH_001',
            'priority' => 'high',
            'retry_count' => 0,
        ];

        $paymentRecord = new PaymentRecord(
            paymentId: 'PAY_TEST',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 5000.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Pending,
            processedAt: $this->processedAt,
            metadata: $originalMetadata
        );

        $updatedRecord = $paymentRecord->withStatus(PaymentStatus::Success);

        // 元数据应该保持不变
        $this->assertEquals($originalMetadata, $updatedRecord->getMetadata());
    }

    public function testMinimumValidAmountShouldSucceed(): void
    {
        $paymentRecord = new PaymentRecord(
            paymentId: 'PAY_MIN',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 0.01,
            method: PaymentMethod::DigitalWallet,
            status: PaymentStatus::Success,
            processedAt: $this->processedAt
        );

        $this->assertEquals(0.01, $paymentRecord->getAmount());
        $this->assertTrue($paymentRecord->isSuccessful());
    }

    public function testLargeAmountShouldBeHandledCorrectly(): void
    {
        $largeAmount = 999999.99;

        $paymentRecord = new PaymentRecord(
            paymentId: 'PAY_LARGE',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: $largeAmount,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Success,
            processedAt: $this->processedAt
        );

        $this->assertEquals($largeAmount, $paymentRecord->getAmount());
        $displayInfo = $paymentRecord->getDisplayInfo();
        $this->assertEquals('999,999.99', $displayInfo['amount']);
    }
}
