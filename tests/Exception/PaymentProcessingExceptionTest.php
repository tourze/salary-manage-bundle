<?php

namespace Tourze\SalaryManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SalaryManageBundle\Exception\PaymentProcessingException;

/**
 * @internal
 */
#[CoversClass(PaymentProcessingException::class)]
class PaymentProcessingExceptionTest extends AbstractExceptionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 父类方法调用
    }

    public function testConstructorWithMessage(): void
    {
        $message = '工资发放失败';
        $exception = new PaymentProcessingException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals([], $exception->getContext());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithContext(): void
    {
        $message = '银行转账失败';
        $context = [
            'employee_id' => 456,
            'payment_method' => 'bank_transfer',
            'amount' => '8500.00',
            'bank_account' => '6228480123456789',
        ];

        $exception = new PaymentProcessingException($message, $context);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($context, $exception->getContext());
    }

    public function testConstructorWithAllParameters(): void
    {
        $message = '发放异常';
        $context = ['error' => 'network_timeout'];
        $code = 500;
        $previous = new \Exception('Previous exception');

        $exception = new PaymentProcessingException($message, $context, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($context, $exception->getContext());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExtendsException(): void
    {
        $exception = new PaymentProcessingException('Test');
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testGetRecoveryHintForNegativeSalary(): void
    {
        $exception = new PaymentProcessingException('实发工资必须大于0');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请检查薪资计算结果，确保实发工资为正数', $hint);
    }

    public function testGetRecoveryHintForUnsupportedPaymentMethod(): void
    {
        $exception = new PaymentProcessingException('不支持的发放方式');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请选择支持的发放方式：银行转账、现金、代发工资或数字钱包', $hint);
    }

    public function testGetRecoveryHintForMissingBankInfo(): void
    {
        $exception = new PaymentProcessingException('需要提供银行信息');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请完善员工银行账户信息', $hint);
    }

    public function testGetRecoveryHintForEmptyBatchList(): void
    {
        $exception = new PaymentProcessingException('批量发放列表不能为空');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请至少选择一个员工进行发放', $hint);
    }

    public function testGetRecoveryHintForUnsupportedCancelOperation(): void
    {
        $exception = new PaymentProcessingException('不支持取消操作');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('只有待处理或处理中的发放记录可以取消', $hint);
    }

    public function testGetRecoveryHintForUnknownError(): void
    {
        $exception = new PaymentProcessingException('未知发放错误');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请检查发放参数和员工信息的完整性', $hint);
    }

    public function testMessageKeywordMatching(): void
    {
        $testCases = [
            ['实发工资必须大于0元', '请检查薪资计算结果，确保实发工资为正数'],
            ['系统不支持的发放方式', '请选择支持的发放方式：银行转账、现金、代发工资或数字钱包'],
            ['员工需要提供银行信息', '请完善员工银行账户信息'],
            ['批量发放列表不能为空', '请至少选择一个员工进行发放'],
            ['当前状态不支持取消操作', '只有待处理或处理中的发放记录可以取消'],
            ['其他发放错误', '请检查发放参数和员工信息的完整性'],
        ];

        foreach ($testCases as [$message, $expectedHint]) {
            $exception = new PaymentProcessingException($message);
            $this->assertEquals($expectedHint, $exception->getRecoveryHint(), "Failed for message: {$message}");
        }
    }

    public function testContextWithPaymentData(): void
    {
        $context = [
            'batch_id' => 'PAY_20250101_001',
            'employee_count' => 50,
            'total_amount' => '125000.00',
            'payment_method' => 'bank_transfer',
            'failed_employees' => [1001, 1005, 1008],
            'error_details' => [
                '1001' => '银行账户信息不完整',
                '1005' => '账户已冻结',
                '1008' => '网络超时',
            ],
        ];

        $exception = new PaymentProcessingException('批量发放部分失败', $context);

        $retrievedContext = $exception->getContext();
        $this->assertEquals($context, $retrievedContext);
        $this->assertEquals('PAY_20250101_001', $retrievedContext['batch_id']);
        $this->assertEquals(50, $retrievedContext['employee_count']);
        $this->assertEquals([1001, 1005, 1008], $retrievedContext['failed_employees']);
    }

    public function testComplexPaymentScenarios(): void
    {
        $scenarios = [
            [
                '实发工资必须大于0：员工张三工资为-100元',
                '请检查薪资计算结果，确保实发工资为正数',
            ],
            [
                '选择的支票发放方式在当前系统中不支持的发放方式',
                '请选择支持的发放方式：银行转账、现金、代发工资或数字钱包',
            ],
            [
                '员工李四选择银行转账但需要提供银行信息',
                '请完善员工银行账户信息',
            ],
            [
                '本次批量发放列表不能为空，请选择员工',
                '请至少选择一个员工进行发放',
            ],
        ];

        foreach ($scenarios as [$message, $expectedHint]) {
            $exception = new PaymentProcessingException($message);
            $this->assertEquals($expectedHint, $exception->getRecoveryHint(), "Failed for message: {$message}");
        }
    }

    public function testPaymentMethodValidation(): void
    {
        $paymentMethods = ['bank_transfer', 'cash', 'payroll', 'digital_wallet'];

        foreach ($paymentMethods as $method) {
            $context = [
                'payment_method' => $method,
                'requires_bank_info' => in_array($method, ['bank_transfer', 'payroll'], true),
                'is_automated' => in_array($method, ['bank_transfer', 'payroll', 'digital_wallet'], true),
            ];

            $exception = new PaymentProcessingException("发放失败：{$method}", $context);
            $this->assertEquals($method, $exception->getContext()['payment_method']);
        }
    }

    public function testExceptionChaining(): void
    {
        $rootCause = new \Exception('Network connection failed');
        $bankException = new \RuntimeException('Bank API error', 0, $rootCause);
        $paymentException = new PaymentProcessingException(
            '银行转账失败',
            ['transaction_id' => 'TXN123456'],
            500,
            $bankException
        );

        $this->assertSame($bankException, $paymentException->getPrevious());
        $this->assertSame($rootCause, $paymentException->getPrevious()->getPrevious());
    }

    public function testBatchPaymentErrorHandling(): void
    {
        $batchContext = [
            'total_employees' => 100,
            'successful_payments' => 85,
            'failed_payments' => 15,
            'failed_reasons' => [
                'insufficient_balance' => 8,
                'invalid_account' => 4,
                'network_timeout' => 3,
            ],
        ];

        $exception = new PaymentProcessingException('批量发放部分失败', $batchContext);
        $context = $exception->getContext();

        $this->assertEquals(100, $context['total_employees']);
        $this->assertEquals(85, $context['successful_payments']);
        $this->assertEquals(15, $context['failed_payments']);
        $this->assertIsArray($context['failed_reasons']);
    }
}
