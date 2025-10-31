<?php

namespace Tourze\SalaryManageBundle\Exception;

class ReportGeneratorException extends \Exception
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        private array $context = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getRecoveryHint(): string
    {
        return match (true) {
            str_contains($this->message, '不支持的导出格式') => '请使用支持的格式: excel, csv, pdf, json',
            str_contains($this->message, '不支持的模板类型') => '请使用支持的模板类型: employee, attendance',
            str_contains($this->message, '文件不存在') => '请检查文件路径是否正确',
            default => '请检查输入参数和系统配置',
        };
    }
}
