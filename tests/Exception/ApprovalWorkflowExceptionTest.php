<?php

namespace Tourze\SalaryManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SalaryManageBundle\Exception\ApprovalWorkflowException;

/**
 * @internal
 */
#[CoversClass(ApprovalWorkflowException::class)]
class ApprovalWorkflowExceptionTest extends AbstractExceptionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 父类方法调用
    }

    public function testConstructorWithAllParameters(): void
    {
        $message = '审批请求不能为空';
        $context = ['request_id' => 'REQ001', 'user_id' => 'USER123'];
        $code = 1001;
        $previous = new \Exception('Previous exception');

        $exception = new ApprovalWorkflowException($message, $context, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($context, $exception->getContext());
        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($previous, $exception->getPrevious());
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $message = '测试异常消息';

        $exception = new ApprovalWorkflowException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals([], $exception->getContext());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testGetContext(): void
    {
        $context = [
            'approval_id' => 'APP001',
            'employee_id' => 'EMP123',
            'department' => '技术部',
            'amount' => 15000.0,
        ];

        $exception = new ApprovalWorkflowException('测试消息', $context);

        $this->assertEquals($context, $exception->getContext());
    }

    public function testGetContextWithEmptyArray(): void
    {
        $exception = new ApprovalWorkflowException('测试消息', []);

        $this->assertEquals([], $exception->getContext());
    }

    #[TestWith(['审批请求不能为空', '请至少选择一个薪资计算记录进行审批'], 'empty request')]
    #[TestWith(['无权审批此请求', '请联系系统管理员分配相应的审批权限'], 'no permission')]
    #[TestWith(['无法审批已完成的请求', '只有待审批状态的请求可以进行审批操作'], 'completed request')]
    #[TestWith(['无法拒绝已处理的请求', '只有待审批状态的请求可以进行拒绝操作'], 'processed request')]
    #[TestWith(['拒绝理由不能为空', '请提供详细的拒绝理由'], 'empty reason')]
    #[TestWith(['未知的审批错误', '请检查审批请求的状态和权限设置'], 'unknown error')]
    public function testGetRecoveryHint(string $message, string $expectedHint): void
    {
        $exception = new ApprovalWorkflowException($message);

        $this->assertEquals($expectedHint, $exception->getRecoveryHint());
    }

    public function testGetRecoveryHintWithMultipleKeywords(): void
    {
        $message = '用户无权审批此薪资计算请求';
        $exception = new ApprovalWorkflowException($message);

        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请联系系统管理员分配相应的审批权限', $hint);
    }

    public function testGetRecoveryHintWithPartialMatch(): void
    {
        $message = '无法审批当前请求';
        $exception = new ApprovalWorkflowException($message);

        $hint = $exception->getRecoveryHint();

        $this->assertEquals('只有待审批状态的请求可以进行审批操作', $hint);
    }

    public function testInheritanceFromException(): void
    {
        $exception = new ApprovalWorkflowException('测试消息');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(ApprovalWorkflowException::class);
        $this->expectExceptionMessage('测试抛出异常');

        throw new ApprovalWorkflowException('测试抛出异常');
    }

    public function testExceptionCanBeCaught(): void
    {
        try {
            throw new ApprovalWorkflowException('测试捕获异常', ['key' => 'value']);
        } catch (ApprovalWorkflowException $e) {
            $this->assertEquals('测试捕获异常', $e->getMessage());
            $this->assertEquals(['key' => 'value'], $e->getContext());
            $this->assertInstanceOf(ApprovalWorkflowException::class, $e);
        }
    }

    public function testExceptionWithComplexContext(): void
    {
        $complexContext = [
            'approval_request' => [
                'id' => 'REQ001',
                'type' => 'salary_approval',
                'status' => 'pending',
                'items' => [
                    ['employee_id' => 'EMP001', 'amount' => 10000],
                    ['employee_id' => 'EMP002', 'amount' => 12000],
                ],
            ],
            'user_info' => [
                'user_id' => 'USER123',
                'role' => 'manager',
                'permissions' => ['view_salary', 'approve_basic'],
            ],
            'timestamp' => '2025-01-15 10:30:00',
            'metadata' => [
                'client_ip' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0',
            ],
        ];

        $exception = new ApprovalWorkflowException(
            '无权审批高额薪资调整',
            $complexContext,
            403
        );

        $this->assertEquals('无权审批高额薪资调整', $exception->getMessage());
        $this->assertEquals($complexContext, $exception->getContext());
        $this->assertEquals(403, $exception->getCode());
        $this->assertEquals('请联系系统管理员分配相应的审批权限', $exception->getRecoveryHint());
    }

    public function testExceptionChaining(): void
    {
        $originalException = new \InvalidArgumentException('原始参数错误');
        $workflowException = new ApprovalWorkflowException(
            '审批流程参数验证失败',
            ['validation' => 'failed'],
            0,
            $originalException
        );

        $this->assertEquals($originalException, $workflowException->getPrevious());
        $this->assertInstanceOf(\InvalidArgumentException::class, $workflowException->getPrevious());
    }

    public function testMultipleRecoveryHintPatterns(): void
    {
        // 测试多个关键词匹配的优先级
        $testCases = [
            ['审批请求不能为空，需要选择员工', '请至少选择一个薪资计算记录进行审批'],
            ['用户无权审批此类请求', '请联系系统管理员分配相应的审批权限'],
            ['无法审批已完成的薪资计算', '只有待审批状态的请求可以进行审批操作'],
            ['无法拒绝已审批的请求', '只有待审批状态的请求可以进行拒绝操作'],
            ['拒绝理由不能为空或太短', '请提供详细的拒绝理由'],
        ];

        foreach ($testCases as [$message, $expectedHint]) {
            $exception = new ApprovalWorkflowException($message);
            $this->assertEquals($expectedHint, $exception->getRecoveryHint(), "Message: {$message}");
        }
    }

    public function testDefaultRecoveryHint(): void
    {
        $messages = [
            '系统内部错误',
            '数据库连接失败',
            '未预期的异常情况',
            '服务暂时不可用',
        ];

        foreach ($messages as $message) {
            $exception = new ApprovalWorkflowException($message);
            $this->assertEquals(
                '请检查审批请求的状态和权限设置',
                $exception->getRecoveryHint(),
                "Message: {$message}"
            );
        }
    }

    public function testContextImmutability(): void
    {
        $originalContext = ['key1' => 'value1', 'key2' => 'value2'];
        $exception = new ApprovalWorkflowException('测试消息', $originalContext);

        $retrievedContext = $exception->getContext();
        $retrievedContext['key3'] = 'value3'; // 尝试修改返回的数组

        // 验证原始上下文未被修改
        $this->assertEquals($originalContext, $exception->getContext());
        $this->assertArrayNotHasKey('key3', $exception->getContext());
    }

    public function testExceptionSerialization(): void
    {
        $exception = new ApprovalWorkflowException(
            '测试序列化',
            ['serialize_test' => true],
            100
        );

        $serialized = serialize($exception);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ApprovalWorkflowException::class, $unserialized);
        $this->assertEquals($exception->getMessage(), $unserialized->getMessage());
        $this->assertEquals($exception->getCode(), $unserialized->getCode());
        // 注意：上下文数据可能在序列化过程中丢失，这取决于具体实现
    }
}
