<?php

namespace Tourze\SalaryManageBundle\Interface;

use Tourze\SalaryManageBundle\Entity\PaymentRecord;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;

interface DataImportExportInterface
{
    /**
     * @param array<int, SalaryCalculation> $salaryCalculations
     */
    public function exportSalaryData(
        array $salaryCalculations,
        string $format = 'excel',
    ): string;

    /**
     * @param array<int, PaymentRecord> $paymentRecords
     */
    public function exportPayrollReport(
        array $paymentRecords,
        string $format = 'pdf',
    ): string;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function importEmployeeData(string $filePath): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function importAttendanceData(string $filePath): array;

    /**
     * @return array<string, array<int, string>|array<string, array<int, string>>>
     */
    public function getSupportedFormats(): array;

    public function validateImportFile(string $filePath, string $type): bool;

    public function getImportTemplate(string $type, string $format = 'excel'): string;
}
