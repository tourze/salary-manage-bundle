<?php

namespace Tourze\SalaryManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SalaryManageBundle\Exception\SalaryCalculationException;

/**
 * @internal
 */
#[CoversClass(SalaryCalculationException::class)]
class SalaryCalculationExceptionTest extends AbstractExceptionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 父类方法调用
    }

    public function testConstructorWithDefaultValues(): void
    {
        $exception = new SalaryCalculationException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithCustomValues(): void
    {
        $message = '薪资计算失败';
        $code = 500;
        $previous = new \Exception('Previous exception');

        $exception = new SalaryCalculationException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new SalaryCalculationException();
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testGetRecoverySuggestionForNegativeTotal(): void
    {
        $exception = new SalaryCalculationException('工资总额不能为负数');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查薪资项目配置，确保没有错误的扣除项目', $suggestion);
    }

    public function testGetRecoverySuggestionForEmptyResult(): void
    {
        $exception = new SalaryCalculationException('薪资计算结果不能为空');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请确保员工至少有一个适用的薪资计算规则', $suggestion);
    }

    public function testGetRecoverySuggestionForRuleError(): void
    {
        $exception = new SalaryCalculationException('基础薪资计算规则配置错误');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查相关计算规则的配置和输入参数', $suggestion);
    }

    public function testGetRecoverySuggestionForUnknownError(): void
    {
        $exception = new SalaryCalculationException('未知的薪资计算错误');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请联系系统管理员检查薪资计算配置', $suggestion);
    }

    public function testGetRecoverySuggestionForEmptyMessage(): void
    {
        $exception = new SalaryCalculationException('');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请联系系统管理员检查薪资计算配置', $suggestion);
    }

    public function testMessageKeywordMatching(): void
    {
        $testCases = [
            ['员工工资总额不能为负数', '请检查薪资项目配置，确保没有错误的扣除项目'],
            ['基础薪资计算结果不能为空', '请确保员工至少有一个适用的薪资计算规则'],
            ['加班计算规则异常', '请检查相关计算规则的配置和输入参数'],
            ['系统内部错误', '请联系系统管理员检查薪资计算配置'],
        ];

        foreach ($testCases as [$message, $expectedSuggestion]) {
            $exception = new SalaryCalculationException($message);
            $this->assertEquals($expectedSuggestion, $exception->getRecoverySuggestion(), "Failed for message: {$message}");
        }
    }

    public function testComplexSalaryCalculationErrors(): void
    {
        $complexCases = [
            [
                '员工张三的工资总额计算结果为-500元，不能为负数',
                '请检查薪资项目配置，确保没有错误的扣除项目',
            ],
            [
                '员工李四没有匹配的薪资计算规则，结果不能为空',
                '请确保员工至少有一个适用的薪资计算规则',
            ],
            [
                '加班费计算规则参数错误，无法完成计算',
                '请检查相关计算规则的配置和输入参数',
            ],
        ];

        foreach ($complexCases as [$message, $expectedSuggestion]) {
            $exception = new SalaryCalculationException($message);
            $this->assertEquals($expectedSuggestion, $exception->getRecoverySuggestion(), "Failed for message: {$message}");
        }
    }

    public function testMultipleKeywordsInMessage(): void
    {
        // 当消息包含多个关键词时，应该匹配第一个
        $exception = new SalaryCalculationException('工资总额计算规则异常，不能为负数');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查薪资项目配置，确保没有错误的扣除项目', $suggestion);
    }

    public function testSalaryComponentErrors(): void
    {
        $componentErrors = [
            '基础工资为0，工资总额不能为负数',
            '津贴补助计算错误，工资总额不能为负数',
            '扣除项目过多，工资总额不能为负数',
            '绩效奖金计算异常，工资总额不能为负数',
        ];

        foreach ($componentErrors as $message) {
            $exception = new SalaryCalculationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertEquals('请检查薪资项目配置，确保没有错误的扣除项目', $suggestion);
        }
    }

    public function testCalculationRuleErrors(): void
    {
        $ruleErrors = [
            '计算规则R001未找到',
            '加班计算规则配置错误',
            '津贴计算规则参数无效',
            '扣除项目计算规则异常',
        ];

        foreach ($ruleErrors as $message) {
            $exception = new SalaryCalculationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertEquals('请检查相关计算规则的配置和输入参数', $suggestion);
        }
    }

    public function testEmptyResultScenarios(): void
    {
        $emptyResultCases = [
            '员工没有适用的薪资项目，计算结果不能为空',
            '所有薪资计算规则都不适用，结果不能为空',
            '薪资期间没有有效的计算数据，结果不能为空',
        ];

        foreach ($emptyResultCases as $message) {
            $exception = new SalaryCalculationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertEquals('请确保员工至少有一个适用的薪资计算规则', $suggestion);
        }
    }

    public function testExceptionChaining(): void
    {
        $rootCause = new \Exception('Database connection failed');
        $serviceException = new \RuntimeException('Salary service unavailable', 0, $rootCause);
        $salaryException = new SalaryCalculationException(
            '薪资计算服务不可用',
            500,
            $serviceException
        );

        $this->assertSame($serviceException, $salaryException->getPrevious());
        $this->assertSame($rootCause, $salaryException->getPrevious()->getPrevious());
    }

    public function testBusinessLogicValidation(): void
    {
        // 测试业务逻辑相关的异常情况
        $businessCases = [
            '员工基本工资低于最低工资标准，工资总额不能为负数',
            '扣除的社保费用超过应发工资，工资总额不能为负数',
            '税前扣除项目金额过大，工资总额不能为负数',
            '请假扣款计算错误，工资总额不能为负数',
        ];

        foreach ($businessCases as $message) {
            $exception = new SalaryCalculationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertIsString($suggestion);
            $this->assertNotEmpty($suggestion);
            $this->assertStringStartsWith('请检查', $suggestion);
        }
    }

    public function testCalculationStageErrors(): void
    {
        // 测试不同计算阶段的错误
        $stageErrors = [
            'pre_calculation' => '薪资预计算阶段出错，计算规则未找到',
            'main_calculation' => '主要薪资计算过程异常',
            'post_calculation' => '薪资后处理阶段失败，结果不能为空',
            'validation' => '薪资计算结果验证失败，总额不能为负数',
        ];

        foreach ($stageErrors as $stage => $message) {
            $exception = new SalaryCalculationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertIsString($suggestion);
            $this->assertNotEmpty($suggestion);
        }
    }

    public function testGetTraceAsString(): void
    {
        $exception = new SalaryCalculationException('Calculation failed');
        $trace = $exception->getTraceAsString();

        $this->assertIsString($trace);
        $this->assertNotEmpty($trace);
    }
}
