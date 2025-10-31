<?php

namespace Tourze\SalaryManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SalaryManageBundle\Exception\TaxCalculationException;

/**
 * @internal
 */
#[CoversClass(TaxCalculationException::class)]
class TaxCalculationExceptionTest extends AbstractExceptionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 父类方法调用
    }

    public function testConstructorWithDefaultValues(): void
    {
        $exception = new TaxCalculationException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithCustomValues(): void
    {
        $message = '个人所得税计算失败';
        $code = 500;
        $previous = new \Exception('Previous exception');

        $exception = new TaxCalculationException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new TaxCalculationException();
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testGetRecoverySuggestionForNegativeTaxableIncome(): void
    {
        $exception = new TaxCalculationException('应税收入不能为负数');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查收入数据计算，确保所有收入项目为正数', $suggestion);
    }

    public function testGetRecoverySuggestionForCumulativeIncomeError(): void
    {
        $exception = new TaxCalculationException('累计收入不能小于当期收入');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查累计收入数据是否正确，累计收入应大于等于当期收入', $suggestion);
    }

    public function testGetRecoverySuggestionForInvalidPeriod(): void
    {
        $exception = new TaxCalculationException('当前期数必须在1-12之间');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查薪资期数设置，期数应该在1-12月之间', $suggestion);
    }

    public function testGetRecoverySuggestionForMissingTaxBracket(): void
    {
        $exception = new TaxCalculationException('无法找到适用的税率档次');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查税率表配置，确保覆盖所有收入区间', $suggestion);
    }

    public function testGetRecoverySuggestionForDeductionTypeError(): void
    {
        $exception = new TaxCalculationException('子女教育扣除类型金额超限');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查专项附加扣除配置，确保金额不超过法定上限', $suggestion);
    }

    public function testGetRecoverySuggestionForUnknownError(): void
    {
        $exception = new TaxCalculationException('未知的税务计算错误');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请联系系统管理员检查税务计算配置和相关参数', $suggestion);
    }

    public function testGetRecoverySuggestionForEmptyMessage(): void
    {
        $exception = new TaxCalculationException('');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请联系系统管理员检查税务计算配置和相关参数', $suggestion);
    }

    public function testMessageKeywordMatching(): void
    {
        $testCases = [
            ['月度应税收入不能为负数', '请检查收入数据计算，确保所有收入项目为正数'],
            ['年度累计收入不能小于当期收入', '请检查累计收入数据是否正确，累计收入应大于等于当期收入'],
            ['税务期数当前期数必须在1-12之间', '请检查薪资期数设置，期数应该在1-12月之间'],
            ['系统无法找到适用的税率档次', '请检查税率表配置，确保覆盖所有收入区间'],
            ['住房贷款扣除类型配置错误', '请检查专项附加扣除配置，确保金额不超过法定上限'],
            ['其他税务错误', '请联系系统管理员检查税务计算配置和相关参数'],
        ];

        foreach ($testCases as [$message, $expectedSuggestion]) {
            $exception = new TaxCalculationException($message);
            $this->assertEquals($expectedSuggestion, $exception->getRecoverySuggestion(), "Failed for message: {$message}");
        }
    }

    public function testComplexTaxCalculationErrors(): void
    {
        $complexCases = [
            [
                '员工张三2025年1月应税收入为-1000元，不能为负数',
                '请检查收入数据计算，确保所有收入项目为正数',
            ],
            [
                '员工李四年度累计收入50000元小于当期收入60000元，不能小于当期收入',
                '请检查累计收入数据是否正确，累计收入应大于等于当期收入',
            ],
            [
                '薪资期间设置错误，当前期数15必须在1-12之间',
                '请检查薪资期数设置，期数应该在1-12月之间',
            ],
            [
                '收入100000元超出税率表范围，无法找到适用的税率档次',
                '请检查税率表配置，确保覆盖所有收入区间',
            ],
            [
                '大病医疗扣除类型金额90000元超过法定上限',
                '请检查专项附加扣除配置，确保金额不超过法定上限',
            ],
        ];

        foreach ($complexCases as [$message, $expectedSuggestion]) {
            $exception = new TaxCalculationException($message);
            $this->assertEquals($expectedSuggestion, $exception->getRecoverySuggestion(), "Failed for message: {$message}");
        }
    }

    public function testMultipleKeywordsInMessage(): void
    {
        // 当消息包含多个关键词时，应该匹配第一个
        $exception = new TaxCalculationException('应税收入累计数据不能为负数');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查收入数据计算，确保所有收入项目为正数', $suggestion);
    }

    public function testIncomeValidationErrors(): void
    {
        $incomeErrors = [
            '基本工资计算后应税收入不能为负数',
            '奖金收入计算错误，应税收入不能为负数',
            '津贴补贴合计后应税收入不能为负数',
            '其他收入项目计算异常，应税收入不能为负数',
        ];

        foreach ($incomeErrors as $message) {
            $exception = new TaxCalculationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertEquals('请检查收入数据计算，确保所有收入项目为正数', $suggestion);
        }
    }

    public function testCumulativeIncomeErrors(): void
    {
        $cumulativeErrors = [
            '1月份累计收入数据异常，不能小于当期收入',
            '年度累计收入计算错误，不能小于当期收入',
            '历史累计收入更新失败，不能小于当期收入',
            '累计预扣数据异常，累计收入不能小于当期收入',
        ];

        foreach ($cumulativeErrors as $message) {
            $exception = new TaxCalculationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertEquals('请检查累计收入数据是否正确，累计收入应大于等于当期收入', $suggestion);
        }
    }

    public function testPeriodValidationErrors(): void
    {
        $periodErrors = [
            '薪资期数设置为0，当前期数必须在1-12之间',
            '年度薪资期数配置为13，当前期数必须在1-12之间',
            '无效的薪资期数，当前期数必须在1-12之间',
        ];

        foreach ($periodErrors as $message) {
            $exception = new TaxCalculationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertEquals('请检查薪资期数设置，期数应该在1-12月之间', $suggestion);
        }
    }

    public function testTaxBracketErrors(): void
    {
        $bracketErrors = [
            '高收入员工无法找到适用的税率档次',
            '税率表配置不完整，无法找到适用的税率档次',
            '收入区间超出范围，无法找到适用的税率档次',
        ];

        foreach ($bracketErrors as $message) {
            $exception = new TaxCalculationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertEquals('请检查税率表配置，确保覆盖所有收入区间', $suggestion);
        }
    }

    public function testDeductionTypeErrors(): void
    {
        $deductionTypes = ['子女教育', '继续教育', '住房贷款', '住房租金', '赡养老人', '大病医疗'];

        foreach ($deductionTypes as $type) {
            $message = "{$type}扣除类型金额超出限制";
            $exception = new TaxCalculationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertEquals('请检查专项附加扣除配置，确保金额不超过法定上限', $suggestion);
        }
    }

    public function testExceptionChaining(): void
    {
        $rootCause = new \Exception('Tax service connection failed');
        $serviceException = new \RuntimeException('Tax calculation service unavailable', 0, $rootCause);
        $taxException = new TaxCalculationException(
            '个人所得税计算服务不可用',
            500,
            $serviceException
        );

        $this->assertSame($serviceException, $taxException->getPrevious());
        $this->assertSame($rootCause, $taxException->getPrevious()->getPrevious());
    }

    public function testTaxCalculationStageErrors(): void
    {
        // 测试不同税务计算阶段的错误
        $stageErrors = [
            'income_calculation' => '收入汇总阶段出错，应税收入不能为负数',
            'deduction_processing' => '扣除项目处理失败，扣除类型配置异常',
            'tax_bracket_lookup' => '税率查询失败，无法找到适用的税率档次',
            'cumulative_calculation' => '累计计算异常，累计收入不能小于当期收入',
            'period_validation' => '期间验证失败，当前期数必须在1-12之间',
        ];

        foreach ($stageErrors as $stage => $message) {
            $exception = new TaxCalculationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertIsString($suggestion);
            $this->assertNotEmpty($suggestion);
        }
    }

    public function testSpecialTaxScenarios(): void
    {
        // 测试特殊税务场景
        $specialCases = [
            '年终奖单独计税应税收入不能为负数',
            '股权激励收入计算错误，应税收入不能为负数',
            '非居民个人税务计算失败，无法找到适用的税率档次',
            '境外收入抵免计算异常，累计收入不能小于当期收入',
        ];

        foreach ($specialCases as $message) {
            $exception = new TaxCalculationException($message);
            $suggestion = $exception->getRecoverySuggestion();

            $this->assertIsString($suggestion);
            $this->assertNotEmpty($suggestion);
            $this->assertStringStartsWith('请检查', $suggestion);
        }
    }

    public function testGetTraceAsString(): void
    {
        $exception = new TaxCalculationException('Tax calculation failed');
        $trace = $exception->getTraceAsString();

        $this->assertIsString($trace);
        $this->assertNotEmpty($trace);
    }
}
