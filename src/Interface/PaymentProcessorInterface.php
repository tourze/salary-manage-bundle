<?php

namespace Tourze\SalaryManageBundle\Interface;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PaymentRecord;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;

interface PaymentProcessorInterface
{
    /**
     * 处理单个员工的薪资发放
     * @param array<string, mixed> $options
     */
    public function processPayment(
        Employee $employee,
        SalaryCalculation $salaryCalculation,
        PayrollPeriod $period,
        array $options = [],
    ): PaymentRecord;

    /**
     * 批量处理薪资发放
     * @param array<int, SalaryCalculation> $salaryCalculations
     * @param array<string, mixed> $options
     * @return array<int, PaymentRecord>
     */
    public function processBatchPayments(
        array $salaryCalculations,
        PayrollPeriod $period,
        array $options = [],
    ): array;

    /**
     * 获取支持的发放方式
     */
    /** @return array<string, string> */
    public function getSupportedMethods(): array;

    /**
     * 验证发放条件
     * @param array<string, mixed> $options
     */
    public function validatePaymentConditions(
        SalaryCalculation $salaryCalculation,
        array $options = [],
    ): bool;

    /**
     * 取消发放
     */
    public function cancelPayment(PaymentRecord $paymentRecord, string $reason): bool;

    /**
     * 查询发放状态
     */
    /** @return array<string, mixed> */
    public function getPaymentStatus(string $paymentId): array;
}
