<?php

namespace Tourze\SalaryManageBundle\Interface;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;

interface ReportGeneratorInterface
{
    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generatePayrollSummaryReport(PayrollPeriod $period, array $options = []): array;

    /**
     * @param array<int, Employee> $employees
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generateTaxReport(PayrollPeriod $period, array $employees = [], array $options = []): array;

    /**
     * @param array<int, Employee> $employees
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generateSocialInsuranceReport(PayrollPeriod $period, array $employees = [], array $options = []): array;

    /**
     * @param array<int, Employee> $employees
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generateIndividualTaxReport(PayrollPeriod $period, array $employees = [], array $options = []): array;

    /**
     * @param array<string, mixed> $reportData
     * @param array<string, mixed> $options
     */
    public function exportReport(array $reportData, string $format, array $options = []): string;

    /**
     * @return array<int, string>
     */
    public function getSupportedFormats(): array;

    /**
     * @return array<string, string>
     */
    public function getReportTypes(): array;
}
