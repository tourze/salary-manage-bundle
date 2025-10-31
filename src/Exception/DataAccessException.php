<?php

namespace Tourze\SalaryManageBundle\Exception;

/**
 * 数据访问异常
 * 当外部数据源访问失败或返回异常数据时抛出
 */
class DataAccessException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取错误恢复建议
     */
    public function getRecoverySuggestion(): string
    {
        return match (true) {
            str_contains($this->getMessage(), '连接') => '请检查网络连接和外部服务状态',
            str_contains($this->getMessage(), '认证') => '请检查API凭证和权限配置',
            str_contains($this->getMessage(), '超时') => '请稍后重试，或联系系统管理员调整超时设置',
            str_contains($this->getMessage(), '格式') => '请检查外部数据源返回的数据格式',
            str_contains($this->getMessage(), '配置') => '请检查相关配置参数是否正确',
            default => '请检查外部数据源连接状态，或联系系统管理员',
        };
    }
}
