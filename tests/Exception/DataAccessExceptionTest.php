<?php

namespace Tourze\SalaryManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SalaryManageBundle\Exception\DataAccessException;

/**
 * @internal
 */
#[CoversClass(DataAccessException::class)]
class DataAccessExceptionTest extends AbstractExceptionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 父类方法调用
    }

    public function testConstructorWithDefaultValues(): void
    {
        $exception = new DataAccessException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithCustomValues(): void
    {
        $message = '数据库连接失败';
        $code = 500;
        $previous = new \Exception('Previous exception');

        $exception = new DataAccessException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new DataAccessException();
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testGetRecoverySuggestionForConnectionError(): void
    {
        $exception = new DataAccessException('网络连接超时');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查网络连接和外部服务状态', $suggestion);
    }

    public function testGetRecoverySuggestionForAuthenticationError(): void
    {
        $exception = new DataAccessException('API认证失败');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查API凭证和权限配置', $suggestion);
    }

    public function testGetRecoverySuggestionForTimeoutError(): void
    {
        $exception = new DataAccessException('请求超时，无法获取数据');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请稍后重试，或联系系统管理员调整超时设置', $suggestion);
    }

    public function testGetRecoverySuggestionForFormatError(): void
    {
        $exception = new DataAccessException('返回数据格式错误');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查外部数据源返回的数据格式', $suggestion);
    }

    public function testGetRecoverySuggestionForConfigurationError(): void
    {
        $exception = new DataAccessException('系统配置参数错误');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查相关配置参数是否正确', $suggestion);
    }

    public function testGetRecoverySuggestionForUnknownError(): void
    {
        $exception = new DataAccessException('未知的数据访问错误');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查外部数据源连接状态，或联系系统管理员', $suggestion);
    }

    public function testGetRecoverySuggestionForEmptyMessage(): void
    {
        $exception = new DataAccessException('');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查外部数据源连接状态，或联系系统管理员', $suggestion);
    }

    public function testMessageKeywordMatching(): void
    {
        $testCases = [
            ['连接失败', '请检查网络连接和外部服务状态'],
            ['认证错误', '请检查API凭证和权限配置'],
            ['请求超时', '请稍后重试，或联系系统管理员调整超时设置'],
            ['数据格式无效', '请检查外部数据源返回的数据格式'],
            ['配置错误', '请检查相关配置参数是否正确'],
            ['其他错误', '请检查外部数据源连接状态，或联系系统管理员'],
        ];

        foreach ($testCases as [$message, $expectedSuggestion]) {
            $exception = new DataAccessException($message);
            $this->assertEquals($expectedSuggestion, $exception->getRecoverySuggestion(), "Failed for message: {$message}");
        }
    }

    public function testMultipleKeywordsInMessage(): void
    {
        // 当消息包含多个关键词时，应该匹配第一个
        $exception = new DataAccessException('连接认证失败');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查网络连接和外部服务状态', $suggestion);
    }

    public function testCaseInsensitiveMatching(): void
    {
        // match表达式是大小写敏感的，所以这里测试实际的大小写
        $exception = new DataAccessException('网络连接异常');
        $suggestion = $exception->getRecoverySuggestion();

        $this->assertEquals('请检查网络连接和外部服务状态', $suggestion);
    }

    public function testComplexErrorMessages(): void
    {
        $complexCases = [
            [
                '数据库连接池已满，无法建立新连接',
                '请检查网络连接和外部服务状态',
            ],
            [
                '用户认证token已过期，请重新登录',
                '请检查API凭证和权限配置',
            ],
            [
                'HTTP请求超时，服务器无响应',
                '请稍后重试，或联系系统管理员调整超时设置',
            ],
            [
                '返回的JSON格式不符合预期',
                '请检查外部数据源返回的数据格式',
            ],
            [
                '缺少必要的配置项',
                '请检查相关配置参数是否正确',
            ],
        ];

        foreach ($complexCases as [$message, $expectedSuggestion]) {
            $exception = new DataAccessException($message);
            $this->assertEquals($expectedSuggestion, $exception->getRecoverySuggestion(), "Failed for message: {$message}");
        }
    }

    public function testExceptionChaining(): void
    {
        $rootCause = new \Exception('Root cause');
        $middleException = new \RuntimeException('Middle exception', 0, $rootCause);
        $dataAccessException = new DataAccessException('Data access failed', 500, $middleException);

        $this->assertSame($middleException, $dataAccessException->getPrevious());
        $this->assertSame($rootCause, $dataAccessException->getPrevious()->getPrevious());
    }

    public function testGetTraceAsString(): void
    {
        $exception = new DataAccessException('Test exception');
        $trace = $exception->getTraceAsString();

        $this->assertIsString($trace);
        $this->assertNotEmpty($trace);
    }
}
