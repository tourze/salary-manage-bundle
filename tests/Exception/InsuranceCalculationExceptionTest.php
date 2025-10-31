<?php

namespace Tourze\SalaryManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SalaryManageBundle\Exception\InsuranceCalculationException;

/**
 * @internal
 */
#[CoversClass(InsuranceCalculationException::class)]
class InsuranceCalculationExceptionTest extends AbstractExceptionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 父类方法调用
    }

    public function testConstructorWithMessage(): void
    {
        $message = '社保计算失败';
        $exception = new InsuranceCalculationException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals([], $exception->getContext());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithContext(): void
    {
        $message = '缴费基数配置错误';
        $context = [
            'employee_id' => 123,
            'region' => 'beijing',
            'year' => 2025,
            'insurance_type' => 'pension',
        ];

        $exception = new InsuranceCalculationException($message, $context);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($context, $exception->getContext());
    }

    public function testConstructorWithAllParameters(): void
    {
        $message = '社保计算异常';
        $context = ['test' => 'data'];
        $code = 500;
        $previous = new \Exception('Previous exception');

        $exception = new InsuranceCalculationException($message, $context, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($context, $exception->getContext());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExtendsException(): void
    {
        $exception = new InsuranceCalculationException('Test');
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testGetRecoveryHintForUnsupportedRegion(): void
    {
        $exception = new InsuranceCalculationException('不支持的地区代码');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请使用支持的地区代码，或联系管理员添加地区配置', $hint);
    }

    public function testGetRecoveryHintForContributionBase(): void
    {
        $exception = new InsuranceCalculationException('缴费基数设置错误');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请检查缴费基数设置，确保符合当地规定', $hint);
    }

    public function testGetRecoveryHintForMissingConfig(): void
    {
        $exception = new InsuranceCalculationException('缺少必要的配置项');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请确保所有保险类型都有对应的缴费基数配置', $hint);
    }

    public function testGetRecoveryHintForYearMismatch(): void
    {
        $exception = new InsuranceCalculationException('年度配置不匹配');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请确保缴费基数年度与工资期间年度一致', $hint);
    }

    public function testGetRecoveryHintForUnknownError(): void
    {
        $exception = new InsuranceCalculationException('未知的社保计算错误');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请检查输入数据的完整性和有效性', $hint);
    }

    public function testGetRecoveryHintForEmptyMessage(): void
    {
        $exception = new InsuranceCalculationException('');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请检查输入数据的完整性和有效性', $hint);
    }

    public function testMessageKeywordMatching(): void
    {
        $testCases = [
            ['系统不支持的地区', '请使用支持的地区代码，或联系管理员添加地区配置'],
            ['缴费基数超出范围', '请检查缴费基数设置，确保符合当地规定'],
            ['缺少保险配置', '请确保所有保险类型都有对应的缴费基数配置'],
            ['年度不匹配', '请确保缴费基数年度与工资期间年度一致'],
            ['计算异常', '请检查输入数据的完整性和有效性'],
        ];

        foreach ($testCases as [$message, $expectedHint]) {
            $exception = new InsuranceCalculationException($message);
            $this->assertEquals($expectedHint, $exception->getRecoveryHint(), "Failed for message: {$message}");
        }
    }

    public function testContextWithTypicalInsuranceData(): void
    {
        $context = [
            'employee_id' => 1001,
            'employee_name' => '张三',
            'region_code' => 'BJ',
            'year' => 2025,
            'month' => 1,
            'base_salary' => '10000.00',
            'insurance_types' => ['pension', 'medical', 'unemployment'],
            'contribution_base' => [
                'pension' => '8000.00',
                'medical' => '8000.00',
                'unemployment' => '8000.00',
            ],
            'error_type' => 'missing_housing_fund_base',
        ];

        $exception = new InsuranceCalculationException(
            '缺少住房公积金缴费基数配置',
            $context
        );

        $retrievedContext = $exception->getContext();
        $this->assertEquals($context, $retrievedContext);
        $this->assertEquals(1001, $retrievedContext['employee_id']);
        $this->assertEquals('张三', $retrievedContext['employee_name']);
        $this->assertEquals('BJ', $retrievedContext['region_code']);
    }

    public function testComplexErrorMessages(): void
    {
        $complexCases = [
            [
                '不支持的地区：北京保险类型配置',
                '请使用支持的地区代码，或联系管理员添加地区配置',
            ],
            [
                '员工缴费基数低于最低标准',
                '请检查缴费基数设置，确保符合当地规定',
            ],
            [
                '系统缺少医疗保险费率配置',
                '请确保所有保险类型都有对应的缴费基数配置',
            ],
            [
                '2025年度配置未找到',
                '请确保缴费基数年度与工资期间年度一致',
            ],
        ];

        foreach ($complexCases as [$message, $expectedHint]) {
            $exception = new InsuranceCalculationException($message);
            $this->assertEquals($expectedHint, $exception->getRecoveryHint(), "Failed for message: {$message}");
        }
    }

    public function testMultipleKeywordsInMessage(): void
    {
        // 当消息包含多个关键词时，应该匹配第一个
        $exception = new InsuranceCalculationException('不支持的地区缺少配置');
        $hint = $exception->getRecoveryHint();

        $this->assertEquals('请使用支持的地区代码，或联系管理员添加地区配置', $hint);
    }

    public function testEmptyContext(): void
    {
        $exception = new InsuranceCalculationException('测试异常');
        $this->assertEquals([], $exception->getContext());
    }

    public function testExceptionChaining(): void
    {
        $rootCause = new \Exception('Database connection failed');
        $middlewareException = new \RuntimeException('Cannot load insurance config', 0, $rootCause);
        $insuranceException = new InsuranceCalculationException(
            '社保计算失败：无法加载配置',
            ['region' => 'shanghai'],
            500,
            $middlewareException
        );

        $this->assertSame($middlewareException, $insuranceException->getPrevious());
        $this->assertSame($rootCause, $insuranceException->getPrevious()->getPrevious());
    }

    public function testContextIntegrity(): void
    {
        $originalContext = [
            'employee' => ['id' => 123, 'name' => '李四'],
            'calculation_params' => ['base' => 5000, 'rate' => 0.08],
            'metadata' => ['timestamp' => time()],
        ];

        $exception = new InsuranceCalculationException('计算异常', $originalContext);
        $retrievedContext = $exception->getContext();

        // 验证上下文数据完整性
        $this->assertEquals($originalContext, $retrievedContext);
        $this->assertIsArray($retrievedContext);
        $this->assertArrayHasKey('employee', $retrievedContext);
        $this->assertIsArray($retrievedContext['employee']);
        $this->assertEquals($originalContext['employee']['id'], $retrievedContext['employee']['id']);
        $this->assertArrayHasKey('calculation_params', $retrievedContext);
        $this->assertIsArray($retrievedContext['calculation_params']);
        $this->assertEquals($originalContext['calculation_params']['base'], $retrievedContext['calculation_params']['base']);
    }

    public function testRegionalErrorScenarios(): void
    {
        $regionalScenarios = [
            ['region' => 'beijing', 'message' => '北京地区不支持此保险类型'],
            ['region' => 'shanghai', 'message' => '上海地区缴费基数配置错误'],
            ['region' => 'guangzhou', 'message' => '广州地区缺少年度配置'],
            ['region' => 'shenzhen', 'message' => '深圳地区保险费率更新失败'],
        ];

        foreach ($regionalScenarios as $scenario) {
            $context = ['region' => $scenario['region'], 'error_time' => date('Y-m-d H:i:s')];
            $exception = new InsuranceCalculationException($scenario['message'], $context);

            $this->assertEquals($scenario['message'], $exception->getMessage());
            $this->assertEquals($scenario['region'], $exception->getContext()['region']);
            $this->assertIsString($exception->getRecoveryHint());
        }
    }
}
