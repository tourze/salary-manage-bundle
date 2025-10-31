<?php

namespace Tourze\SalaryManageBundle\Interface;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\PayslipTemplate;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;

interface PayslipGeneratorInterface
{
    /**
     * 生成单个员工薪资条
     *
     * @param array<string, mixed> $options
     */
    public function generatePayslip(
        Employee $employee,
        SalaryCalculation $salaryCalculation,
        PayrollPeriod $period,
        array $options = [],
    ): string;

    /**
     * 批量生成薪资条
     *
     * @param SalaryCalculation[] $salaryCalculations
     * @param array<string, mixed> $options
     * @return array<int, string>
     */
    public function generateBatchPayslips(
        array $salaryCalculations,
        PayrollPeriod $period,
        array $options = [],
    ): array;

    /**
     * 获取支持的输出格式
     *
     * @return array<int, string>
     */
    public function getSupportedFormats(): array;

    /**
     * 设置薪资条模板
     */
    public function setTemplate(PayslipTemplate $template): void;

    /**
     * 发送薪资条邮件
     *
     * @param array<string, mixed> $options
     */
    public function sendPayslipByEmail(
        Employee $employee,
        string $payslipContent,
        array $options = [],
    ): bool;

    /**
     * 获取薪资条查询链接
     */
    public function getPayslipQueryUrl(Employee $employee, PayrollPeriod $period): string;

    /**
     * 验证薪资条数据完整性
     */
    public function validatePayslipData(SalaryCalculation $salaryCalculation): bool;
}
