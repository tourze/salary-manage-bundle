<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PaymentRecord;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Entity\SalaryItem;
use Tourze\SalaryManageBundle\Enum\PaymentMethod;
use Tourze\SalaryManageBundle\Enum\PaymentStatus;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;
use Tourze\SalaryManageBundle\Exception\PaymentProcessingException;
use Tourze\SalaryManageBundle\Service\PaymentProcessorService;

/**
 * @internal
 */
#[CoversClass(PaymentProcessorService::class)]
class PaymentProcessorServiceTest extends TestCase
{
    private PaymentProcessorService $processor;

    private Employee $employee;

    private PayrollPeriod $period;

    private SalaryCalculation $salaryCalculation;

    protected function setUp(): void
    {
        $this->processor = new PaymentProcessorService();

        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('E001');
        $this->employee->setName('张三');
        $this->employee->setBaseSalary('10000.00');
        $this->employee->setDepartment('技术部');
        $this->employee->setHireDate(new \DateTimeImmutable('2023-01-01'));

        $this->period = new PayrollPeriod();
        $this->period->setYear(2025);
        $this->period->setMonth(1);

        $this->salaryCalculation = $this->createMockSalaryCalculation();
    }

    public function testProcessPayment(): void
    {
        $options = ['method' => 'bank_transfer', 'bank_info' => ['account_number' => '123']];

        $paymentRecord = $this->processor->processPayment(
            $this->employee,
            $this->salaryCalculation,
            $this->period,
            $options
        );

        $this->assertInstanceOf(PaymentRecord::class, $paymentRecord);
        $this->assertEquals(PaymentMethod::BankTransfer, $paymentRecord->getMethod());
        $this->assertEquals(PaymentStatus::Success, $paymentRecord->getStatus());
        $this->assertEquals(8500.0, $paymentRecord->getAmount());
    }

    public function testProcessBankTransferPayment(): void
    {
        $options = [
            'method' => 'bank_transfer',
            'bank_info' => [
                'account_number' => '1234567890',
                'bank_name' => '招商银行',
            ],
        ];

        $paymentRecord = $this->processor->processPayment(
            $this->employee,
            $this->salaryCalculation,
            $this->period,
            $options
        );

        $this->assertInstanceOf(PaymentRecord::class, $paymentRecord);
        $this->assertEquals(PaymentMethod::BankTransfer, $paymentRecord->getMethod());
        $this->assertEquals(PaymentStatus::Success, $paymentRecord->getStatus());
        $this->assertEquals(8500.0, $paymentRecord->getAmount());
        $this->assertNotNull($paymentRecord->getBankTransactionId());
        $this->assertStringStartsWith('TXN_', $paymentRecord->getBankTransactionId());
    }

    public function testProcessCashPayment(): void
    {
        $options = ['method' => 'cash'];

        $paymentRecord = $this->processor->processPayment(
            $this->employee,
            $this->salaryCalculation,
            $this->period,
            $options
        );

        $this->assertEquals(PaymentMethod::Cash, $paymentRecord->getMethod());
        $this->assertEquals(PaymentStatus::Success, $paymentRecord->getStatus());
        $this->assertNull($paymentRecord->getBankTransactionId());
    }

    public function testProcessDigitalWalletPayment(): void
    {
        $options = [
            'method' => 'digital_wallet',
            'wallet_info' => [
                'wallet_id' => 'wallet_123',
                'provider' => '支付宝',
            ],
        ];

        $paymentRecord = $this->processor->processPayment(
            $this->employee,
            $this->salaryCalculation,
            $this->period,
            $options
        );

        $this->assertEquals(PaymentMethod::DigitalWallet, $paymentRecord->getMethod());
        $this->assertEquals(PaymentStatus::Success, $paymentRecord->getStatus());
        $transactionId = $paymentRecord->getBankTransactionId();
        $this->assertNotNull($transactionId, 'Transaction ID should not be null');
        $this->assertStringStartsWith('WALLET_', $transactionId);
    }

    public function testProcessBatchPayments(): void
    {
        $salaryCalculations = [
            $this->salaryCalculation,
            $this->createMockSalaryCalculation('李四', 'E002', 12000.0),
            $this->createMockSalaryCalculation('王五', 'E003', 8000.0),
        ];

        $options = [
            'method' => 'bank_transfer',
            'bank_info' => ['account_number' => '1234567890'],
        ];

        $results = $this->processor->processBatchPayments(
            $salaryCalculations,
            $this->period,
            $options
        );

        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(PaymentRecord::class, $result);
            $this->assertEquals(PaymentStatus::Success, $result->getStatus());
            $this->assertArrayHasKey('batch_id', $result->getMetadata());
        }

        // 验证批次ID一致
        $batchIds = array_map(fn ($r) => $r->getMetadata()['batch_id'], $results);
        $this->assertCount(1, array_unique($batchIds));
    }

    public function testGetSupportedMethods(): void
    {
        $methods = $this->processor->getSupportedMethods();

        $this->assertIsArray($methods);
        $this->assertArrayHasKey('bank_transfer', $methods);
        $this->assertArrayHasKey('cash', $methods);
        $this->assertArrayHasKey('payroll', $methods);
        $this->assertArrayHasKey('digital_wallet', $methods);

        $this->assertEquals('银行转账', $methods['bank_transfer']);
        $this->assertEquals('现金发放', $methods['cash']);
    }

    public function testValidatePaymentConditions(): void
    {
        $validOptions = [
            'method' => 'bank_transfer',
            'bank_info' => ['account_number' => '1234567890'],
        ];

        $this->assertTrue($this->processor->validatePaymentConditions(
            $this->salaryCalculation,
            $validOptions
        ));
    }

    public function testValidatePaymentConditionsFailsForZeroSalary(): void
    {
        $zeroSalaryCalculation = $this->createMockSalaryCalculation('张三', 'E001', 0.0);

        $this->expectException(PaymentProcessingException::class);
        $this->expectExceptionMessage('实发工资必须大于0');

        $this->processor->validatePaymentConditions($zeroSalaryCalculation, []);
    }

    public function testValidatePaymentConditionsFailsForUnsupportedMethod(): void
    {
        $options = ['method' => 'unsupported_method'];

        $this->expectException(PaymentProcessingException::class);
        $this->expectExceptionMessage('不支持的发放方式: unsupported_method');

        $this->processor->validatePaymentConditions($this->salaryCalculation, $options);
    }

    public function testValidatePaymentConditionsFailsForMissingBankInfo(): void
    {
        $options = ['method' => 'bank_transfer'];

        $this->expectException(PaymentProcessingException::class);
        $this->expectExceptionMessage('发放方式 银行转账 需要提供银行信息');

        $this->processor->validatePaymentConditions($this->salaryCalculation, $options);
    }

    public function testProcessBatchPaymentsWithEmptyList(): void
    {
        $this->expectException(PaymentProcessingException::class);
        $this->expectExceptionMessage('批量发放列表不能为空');

        $this->processor->processBatchPayments([], $this->period, []);
    }

    public function testGetPaymentStatus(): void
    {
        $paymentId = 'PAY_TEST_123';

        $status = $this->processor->getPaymentStatus($paymentId);

        $this->assertIsArray($status);
        $this->assertEquals($paymentId, $status['payment_id']);
        $this->assertEquals('success', $status['status']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $status['last_updated']);
    }

    public function testPaymentIdGeneration(): void
    {
        $paymentRecord1 = $this->processor->processPayment(
            $this->employee,
            $this->salaryCalculation,
            $this->period,
            ['method' => 'cash']
        );

        // 添加微秒延迟确保时间戳不同
        usleep(1000);

        $paymentRecord2 = $this->processor->processPayment(
            $this->employee,
            $this->salaryCalculation,
            $this->period,
            ['method' => 'cash']
        );

        // 支付ID应该是唯一的
        $this->assertNotEquals($paymentRecord1->getPaymentId(), $paymentRecord2->getPaymentId());

        // 支付ID应该包含期间和员工编号
        $this->assertStringContainsString('PAY_2025-01', $paymentRecord1->getPaymentId());
        $this->assertStringContainsString('E001', $paymentRecord1->getPaymentId());
    }

    public function testPaymentRecordDisplayInfo(): void
    {
        $paymentRecord = $this->processor->processPayment(
            $this->employee,
            $this->salaryCalculation,
            $this->period,
            ['method' => 'bank_transfer', 'bank_info' => ['account_number' => '123']]
        );

        $displayInfo = $paymentRecord->getDisplayInfo();

        $this->assertIsArray($displayInfo);
        $this->assertEquals('张三', $displayInfo['employee_name']);
        $this->assertEquals('8,500.00', $displayInfo['amount']);
        $this->assertEquals('银行转账', $displayInfo['method']);
        $this->assertEquals('成功', $displayInfo['status']);
        $this->assertArrayHasKey('processed_at', $displayInfo);
        $this->assertArrayHasKey('bank_transaction_id', $displayInfo);
    }

    public function testCancelPayment(): void
    {
        // 创建一个处理中的支付记录用于测试取消操作
        $paymentRecord = new PaymentRecord(
            paymentId: 'PAY_TEST_001',
            employee: $this->employee,
            salaryCalculation: $this->salaryCalculation,
            period: $this->period,
            amount: 8500.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Processing, // 使用可取消的状态
            processedAt: new \DateTimeImmutable(),
            metadata: ['bank_info' => ['account_number' => '123']]
        );

        // 测试取消支付
        $result = $this->processor->cancelPayment($paymentRecord, '员工请假，暂停发放');

        $this->assertTrue($result);
    }

    public function testCancelPaymentFailsForNonCancellableStatus(): void
    {
        // 创建一个已完成的支付记录
        $paymentRecord = $this->processor->processPayment(
            $this->employee,
            $this->salaryCalculation,
            $this->period,
            ['method' => 'cash']
        );

        // 现金支付通常不能取消
        $this->expectException(PaymentProcessingException::class);
        $this->expectExceptionMessage('不支持取消操作');

        $this->processor->cancelPayment($paymentRecord, '测试取消');
    }

    public function testAddBankAdapter(): void
    {
        // 创建一个模拟的银行适配器
        $mockAdapter = new class {
            public function processPayment(): bool
            {
                return true;
            }
        };

        // 测试添加银行适配器
        $result = $this->processor->addBankAdapter('ICBC', $mockAdapter);

        // 验证返回self以支持链式调用
        $this->assertSame($this->processor, $result);

        // 验证适配器已被添加
        $retrievedAdapter = $this->processor->getBankAdapter('ICBC');
        $this->assertSame($mockAdapter, $retrievedAdapter);

        // 测试添加多个适配器
        $anotherAdapter = new class {
            public function transferFunds(): string
            {
                return 'success';
            }
        };

        $this->processor->addBankAdapter('BOC', $anotherAdapter);

        // 验证两个适配器都存在且独立
        $this->assertSame($mockAdapter, $this->processor->getBankAdapter('ICBC'));
        $this->assertSame($anotherAdapter, $this->processor->getBankAdapter('BOC'));

        // 测试不存在的适配器返回null
        $this->assertNull($this->processor->getBankAdapter('NonExistent'));
    }

    private function createMockSalaryCalculation(
        string $name = '张三',
        string $employeeNumber = 'E001',
        float $netSalary = 8500.0,
    ): SalaryCalculation {
        $employee = new Employee();
        $employee->setEmployeeNumber($employeeNumber);
        $employee->setName($name);
        $employee->setBaseSalary('10000.00');
        $employee->setHireDate(new \DateTimeImmutable('2023-01-01'));

        $salaryCalculation = new SalaryCalculation();
        $salaryCalculation->setEmployee($employee);
        $salaryCalculation->setPeriod($this->period);

        // 添加薪资项目来模拟计算结果
        $basicSalaryItem = new SalaryItem();
        $basicSalaryItem->setType(SalaryItemType::BasicSalary);
        $basicSalaryItem->setAmount(10000.0);
        $basicSalaryItem->setDescription('基本工资');

        $deductionItem = new SalaryItem();
        $deductionItem->setType(SalaryItemType::Allowance);
        $deductionItem->setAmount(-(10000.0 - $netSalary));
        $deductionItem->setDescription('扣款');

        $salaryCalculation->addItem($basicSalaryItem);
        $salaryCalculation->addItem($deductionItem);

        return $salaryCalculation;
    }
}
