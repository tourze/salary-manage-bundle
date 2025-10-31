<?php

namespace Tourze\SalaryManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * @internal
 */
#[CoversClass(DataValidationException::class)]
class DataValidationExceptionTest extends AbstractExceptionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 父类方法调用
    }

    public function testConstructorWithDefaultValues(): void
    {
        $exception = new DataValidationException();

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithCustomValues(): void
    {
        $message = '员工编号不能为空';
        $code = 400;
        $previous = new \Exception('Previous exception');

        $exception = new DataValidationException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExtendsInvalidArgumentException(): void
    {
        $exception = new DataValidationException();
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    public function testGetRecoverySuggestionForEmployeeNumber(): void
    {
        $exception = new DataValidationException('员工编号格式不正确');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查员工编号格式，确保符合系统要求', $suggestion);
    }

    public function testGetRecoverySuggestionForBaseSalary(): void
    {
        $exception = new DataValidationException('基本薪资必须大于0');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查基本薪资金额，确保为有效数值', $suggestion);
    }

    public function testGetRecoverySuggestionForDate(): void
    {
        $exception = new DataValidationException('入职日期不能为空');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查日期格式，确保为有效的日期值', $suggestion);
    }

    public function testGetRecoverySuggestionForAmount(): void
    {
        $exception = new DataValidationException('扣除金额不能为负数');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查金额数值，确保为正数且格式正确', $suggestion);
    }

    public function testGetRecoverySuggestionForType(): void
    {
        $exception = new DataValidationException('扣除类型不在允许的范围内');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查输入的类型值是否在允许的范围内', $suggestion);
    }

    public function testGetRecoverySuggestionForUnknownError(): void
    {
        $exception = new DataValidationException('未知的验证错误');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查输入数据的格式和内容是否符合要求', $suggestion);
    }

    public function testGetRecoverySuggestionForEmptyMessage(): void
    {
        $exception = new DataValidationException('');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查输入数据的格式和内容是否符合要求', $suggestion);
    }

    public function testMessageKeywordMatching(): void
    {
        $testCases = [
            ['员工编号不能为空', '请检查员工编号格式，确保符合系统要求'],
            ['基本薪资格式错误', '请检查基本薪资金额，确保为有效数值'],
            ['生日日期无效', '请检查日期格式，确保为有效的日期值'],
            ['税额金额超出范围', '请检查金额数值，确保为正数且格式正确'],
            ['保险类型不正确', '请检查输入的类型值是否在允许的范围内'],
            ['其他验证错误', '请检查输入数据的格式和内容是否符合要求'],
        ];

        foreach ($testCases as [$message, $expectedSuggestion]) {
            $exception = new DataValidationException($message);
            $this->assertEquals($expectedSuggestion, $exception->getRecoverySuggestion(), "Failed for message: {$message}");
        }
    }

    public function testMultipleKeywordsInMessage(): void
    {
        // 当消息包含多个关键词时，应该匹配第一个
        $exception = new DataValidationException('员工编号类型不匹配');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查员工编号格式，确保符合系统要求', $suggestion);
    }

    public function testComplexValidationMessages(): void
    {
        $complexCases = [
            [
                '员工编号长度必须在3-20个字符之间',
                '请检查员工编号格式，确保符合系统要求',
            ],
            [
                '基本薪资不能超过公司规定的上限',
                '请检查基本薪资金额，确保为有效数值',
            ],
            [
                '入职日期不能早于公司成立日期',
                '请检查日期格式，确保为有效的日期值',
            ],
            [
                '专项扣除金额超过法定上限',
                '请检查金额数值，确保为正数且格式正确',
            ],
            [
                '薪资项目类型不在系统支持的范围内',
                '请检查输入的类型值是否在允许的范围内',
            ],
        ];

        foreach ($complexCases as [$message, $expectedSuggestion]) {
            $exception = new DataValidationException($message);
            $this->assertEquals($expectedSuggestion, $exception->getRecoverySuggestion(), "Failed for message: {$message}");
        }
    }

    public function testValidationRuleTypes(): void
    {
        // 测试不同类型的验证规则
        $validationCases = [
            // 格式验证
            ['员工编号格式不符合要求', '员工编号'],
            ['基本薪资必须是数字', '基本薪资'],
            ['出生日期格式错误', '日期'],
            ['扣除金额必须为正数', '金额'],
            ['薪资类型值无效', '类型'],

            // 范围验证
            ['员工编号长度超限', '员工编号'],
            ['基本薪资超出合理范围', '基本薪资'],
            ['日期超出允许范围', '日期'],
            ['金额超过限制', '金额'],
            ['类型不在枚举范围内', '类型'],

            // 必填验证
            ['员工编号不能为空', '员工编号'],
            ['基本薪资为必填项', '基本薪资'],
            ['入职日期不能为空', '日期'],
            ['扣除金额不能为空', '金额'],
            ['薪资类型必须指定', '类型'],
        ];

        foreach ($validationCases as [$message, $expectedKeyword]) {
            $exception = new DataValidationException($message);
            $suggestion = $exception->getRecoverySuggestion();
            $this->assertStringContainsString($expectedKeyword, $suggestion, "Suggestion should contain keyword for message: {$message}");
        }
    }

    public function testExceptionChaining(): void
    {
        $rootCause = new \Exception('Root validation error');
        $middlewareException = new \InvalidArgumentException('Middleware validation', 0, $rootCause);
        $dataValidationException = new DataValidationException('Final validation failed', 400, $middlewareException);

        $this->assertSame($middlewareException, $dataValidationException->getPrevious());
        $this->assertSame($rootCause, $dataValidationException->getPrevious()->getPrevious());
    }

    public function testBusinessRuleValidationMessages(): void
    {
        // 测试业务规则相关的验证消息
        $businessRuleCases = [
            '员工编号已存在，请使用其他编号',
            '基本薪资低于最低工资标准',
            '入职日期不能是未来的日期',
            '专项扣除金额超过该类型的最大限额',
            '薪资项目类型与员工岗位不匹配',
        ];

        foreach ($businessRuleCases as $message) {
            $exception = new DataValidationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertIsString($suggestion);
            $this->assertNotEmpty($suggestion);
            $this->assertStringStartsWith('请检查', $suggestion);
        }
    }

    public function testGetTraceAsString(): void
    {
        $exception = new DataValidationException('Validation failed');
        $trace = $exception->getTraceAsString();

        $this->assertIsString($trace);
        $this->assertNotEmpty($trace);
    }
}
