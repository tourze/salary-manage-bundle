<?php

namespace Tourze\SalaryManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SalaryManageBundle\Exception\ReportGeneratorException;

/**
 * @internal
 */
#[CoversClass(ReportGeneratorException::class)]
class ReportGeneratorExceptionTest extends AbstractExceptionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 父类方法调用
    }

    public function testConstructorWithBasicMessage(): void
    {
        $message = '测试错误信息';
        $exception = new ReportGeneratorException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertEmpty($exception->getContext());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithAllParameters(): void
    {
        $message = '完整错误信息';
        $context = ['key' => 'value', 'number' => 123];
        $code = 500;
        $previous = new \InvalidArgumentException('前置异常');

        $exception = new ReportGeneratorException($message, $context, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($context, $exception->getContext());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testGetContext(): void
    {
        $context = [
            'report_type' => 'payroll_summary',
            'period' => '2025-01',
            'employee_count' => 100,
        ];

        $exception = new ReportGeneratorException('测试', $context);

        $this->assertEquals($context, $exception->getContext());
    }

    public function testGetRecoveryHintForUnsupportedFormat(): void
    {
        $exception = new ReportGeneratorException('不支持的导出格式: xml');

        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请使用支持的格式: excel, csv, pdf, json', $hint);
    }

    public function testGetRecoveryHintForUnsupportedTemplate(): void
    {
        $exception = new ReportGeneratorException('不支持的模板类型: invalid_template');

        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请使用支持的模板类型: employee, attendance', $hint);
    }

    public function testGetRecoveryHintForFileNotFound(): void
    {
        $exception = new ReportGeneratorException('文件不存在: /path/to/missing/file.xlsx');

        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请检查文件路径是否正确', $hint);
    }

    public function testGetRecoveryHintForGenericError(): void
    {
        $exception = new ReportGeneratorException('其他类型的错误信息');

        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请检查输入参数和系统配置', $hint);
    }

    public function testGetRecoveryHintWithMultipleKeywords(): void
    {
        $exception = new ReportGeneratorException('处理文件时发现不支持的导出格式: yaml');

        $hint = $exception->getRecoveryHint();

        // match 会返回第一个匹配的情况
        $this->assertEquals('请使用支持的格式: excel, csv, pdf, json', $hint);
    }

    public function testExceptionIsInstanceOfException(): void
    {
        $exception = new ReportGeneratorException('测试');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(ReportGeneratorException::class);
        $this->expectExceptionMessage('抛出异常测试');

        throw new ReportGeneratorException('抛出异常测试');
    }

    public function testExceptionCanBeCaught(): void
    {
        $caught = false;
        $message = '';

        try {
            throw new ReportGeneratorException('捕获异常测试', ['test' => true]);
        } catch (ReportGeneratorException $e) {
            $caught = true;
            $message = $e->getMessage();
        }

        $this->assertTrue($caught);
        $this->assertEquals('捕获异常测试', $message);
    }

    public function testContextWithComplexData(): void
    {
        $context = [
            'report_data' => [
                'headers' => ['员工编号', '姓名'],
                'data' => [['E001', '张三'], ['E002', '李四']],
            ],
            'options' => [
                'format' => 'excel',
                'include_summary' => true,
            ],
            'timestamp' => '2025-01-15 10:30:00',
        ];

        $exception = new ReportGeneratorException('复杂数据测试', $context);

        $retrievedContext = $exception->getContext();
        $this->assertEquals($context, $retrievedContext);
        $this->assertIsArray($retrievedContext['report_data']);
        $this->assertIsArray($retrievedContext['options']);
    }

    public function testEmptyContextByDefault(): void
    {
        $exception = new ReportGeneratorException('无上下文测试');

        $this->assertEmpty($exception->getContext());
        $this->assertIsArray($exception->getContext());
    }
}
