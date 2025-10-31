<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Entity\PaymentRecord;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Exception\DataValidationException;
use Tourze\SalaryManageBundle\Interface\DataImportExportInterface;

class DataImportExportService implements DataImportExportInterface
{
    public function __construct(
        /** @var array<string, mixed> */
        private array $config = [],
    ) {
        $this->config = array_merge([
            'export_path' => sys_get_temp_dir(),
            'allowed_formats' => ['excel', 'csv', 'pdf'],
            'max_file_size' => 10 * 1024 * 1024, // 10MB
        ], $config);
    }

    /**
     * @param array<int, SalaryCalculation> $salaryCalculations
     */
    public function exportSalaryData(
        array $salaryCalculations,
        string $format = 'excel',
    ): string {
        $this->validateFormat($format);

        $filename = 'salary_export_' . date('Y-m-d_H-i-s') . '.' . $this->getFileExtension($format);
        $exportPath = is_string($this->config['export_path']) ? $this->config['export_path'] : sys_get_temp_dir();
        $filepath = $exportPath . '/' . $filename;

        switch ($format) {
            case 'excel':
                return $this->exportToExcel($salaryCalculations, $filepath);
            case 'csv':
                return $this->exportToCsv($salaryCalculations, $filepath);
            case 'pdf':
                return $this->exportToPdf($salaryCalculations, $filepath);
            default:
                throw new DataValidationException("不支持的导出格式: {$format}");
        }
    }

    /**
     * @param array<int, PaymentRecord> $paymentRecords
     */
    public function exportPayrollReport(
        array $paymentRecords,
        string $format = 'pdf',
    ): string {
        $this->validateFormat($format);

        $filename = 'payroll_report_' . date('Y-m-d_H-i-s') . '.' . $this->getFileExtension($format);
        $exportPath = is_string($this->config['export_path']) ? $this->config['export_path'] : sys_get_temp_dir();
        $filepath = $exportPath . '/' . $filename;

        switch ($format) {
            case 'pdf':
                return $this->generatePayrollPdf($paymentRecords, $filepath);
            case 'excel':
                return $this->generatePayrollExcel($paymentRecords, $filepath);
            default:
                throw new DataValidationException("不支持的报告格式: {$format}");
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function importEmployeeData(string $filePath): array
    {
        $this->validateImportFile($filePath, 'employee');

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return match ($extension) {
            'xlsx', 'xls' => $this->importExcelEmployees($filePath),
            'csv' => $this->importCsvEmployees($filePath),
            default => throw new DataValidationException("不支持的文件格式: {$extension}"),
        };
    }

    /** @return array<int, array<string, mixed>> */
    public function importAttendanceData(string $filePath): array
    {
        $this->validateImportFile($filePath, 'attendance');

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return match ($extension) {
            'xlsx', 'xls' => $this->importExcelAttendance($filePath),
            'csv' => $this->importCsvAttendance($filePath),
            default => throw new DataValidationException("不支持的文件格式: {$extension}"),
        };
    }

    /** @return array<string, array<int, string>|array<string, array<int, string>>> */
    public function getSupportedFormats(): array
    {
        return [
            'export' => ['excel', 'csv', 'pdf'],
            'import' => ['excel', 'csv'],
            'extensions' => [
                'excel' => ['xlsx', 'xls'],
                'csv' => ['csv'],
                'pdf' => ['pdf'],
            ],
        ];
    }

    public function validateImportFile(string $filePath, string $type): bool
    {
        if (!file_exists($filePath)) {
            throw new DataValidationException("文件不存在: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new DataValidationException("文件不可读: {$filePath}");
        }

        $maxFileSize = is_int($this->config['max_file_size']) ? $this->config['max_file_size'] : 10485760;
        if (filesize($filePath) > $maxFileSize) {
            throw new DataValidationException('文件大小超过限制');
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $supportedExtensions = array_merge(['xlsx', 'xls', 'csv']);

        if (!in_array(strtolower($extension), $supportedExtensions, true)) {
            throw new DataValidationException("不支持的文件格式: {$extension}");
        }

        return $this->validateFileContent($filePath, $type);
    }

    public function getImportTemplate(string $type, string $format = 'excel'): string
    {
        $templates = [
            'employee' => [
                'headers' => ['员工编号', '姓名', '部门', '岗位', '基本工资', '入职日期'],
                'sample' => ['E001', '张三', '技术部', '软件工程师', '10000', '2023-01-01'],
            ],
            'attendance' => [
                'headers' => ['员工编号', '日期', '签到时间', '签退时间', '工作小时', '加班小时'],
                'sample' => ['E001', '2025-01-01', '09:00', '18:00', '8', '2'],
            ],
        ];

        if (!isset($templates[$type])) {
            throw new DataValidationException("不支持的模板类型: {$type}");
        }

        return $this->generateTemplate($templates[$type], $format);
    }

    private function validateFormat(string $format): void
    {
        $allowedFormats = is_array($this->config['allowed_formats']) ? $this->config['allowed_formats'] : ['excel', 'csv', 'pdf'];
        if (!in_array($format, $allowedFormats, true)) {
            throw new DataValidationException("不支持的格式: {$format}");
        }
    }

    private function getFileExtension(string $format): string
    {
        return match ($format) {
            'excel' => 'xlsx',
            'csv' => 'csv',
            'pdf' => 'pdf',
            default => $format,
        };
    }

    /**
     * @param array<int, SalaryCalculation> $salaryCalculations
     */
    private function exportToExcel(
        array $salaryCalculations,
        string $filepath,
    ): string {
        $data = [];
        $data[] = ['员工编号', '姓名', '部门', '基本工资', '应发工资', '扣除项', '实发工资'];

        foreach ($salaryCalculations as $calculation) {
            $employee = $calculation->getEmployee();
            $data[] = [
                $employee->getEmployeeNumber(),
                $employee->getName(),
                $employee->getDepartment(),
                $employee->getBaseSalary(),
                $calculation->getGrossAmount(),
                $calculation->getDeductionsAmount(),
                $calculation->getNetAmount(),
            ];
        }

        $csvData = array_map(fn ($row) => array_map('strval', $row), $data);
        file_put_contents($filepath, $this->arrayToCsv($csvData));

        return $filepath;
    }

    /**
     * @param array<int, SalaryCalculation> $salaryCalculations
     */
    private function exportToCsv(
        array $salaryCalculations,
        string $filepath,
    ): string {
        return $this->exportToExcel($salaryCalculations, $filepath);
    }

    /**
     * @param array<int, SalaryCalculation> $salaryCalculations
     */
    private function exportToPdf(
        array $salaryCalculations,
        string $filepath,
    ): string {
        $html = $this->generateSalaryHtml($salaryCalculations);

        file_put_contents($filepath, $html);

        return $filepath;
    }

    /**
     * @param array<int, PaymentRecord> $paymentRecords
     */
    private function generatePayrollPdf(
        array $paymentRecords,
        string $filepath,
    ): string {
        $html = $this->generatePayrollHtml($paymentRecords);

        file_put_contents($filepath, $html);

        return $filepath;
    }

    /**
     * @param array<int, PaymentRecord> $paymentRecords
     */
    private function generatePayrollExcel(
        array $paymentRecords,
        string $filepath,
    ): string {
        $data = [];
        $data[] = ['支付ID', '员工编号', '员工姓名', '发放金额', '发放方式', '状态', '处理时间'];

        foreach ($paymentRecords as $record) {
            $employee = $record->getEmployee();
            $data[] = [
                $record->getPaymentId(),
                $employee->getEmployeeNumber(),
                $employee->getName(),
                $record->getAmount(),
                $record->getMethod()->getLabel(),
                $record->getStatus()->getLabel(),
                $record->getProcessedAt()->format('Y-m-d H:i:s'),
            ];
        }

        $csvData = array_map(fn ($row) => array_map('strval', $row), $data);
        file_put_contents($filepath, $this->arrayToCsv($csvData));

        return $filepath;
    }

    /** @return array<int, array<string, mixed>> */
    private function importExcelEmployees(string $filePath): array
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (false === $lines || [] === $lines) {
            throw new DataValidationException("无法读取文件或文件为空: {$filePath}");
        }

        $headerLine = array_shift($lines);
        if (false === $headerLine) {
            throw new DataValidationException("文件为空: {$filePath}");
        }

        $header = str_getcsv($headerLine, ',', '"', '');
        $headerCount = count($header);

        $employees = [];
        foreach ($lines as $line) {
            $data = str_getcsv($line, ',', '"', '');
            if (count($data) === $headerCount && !in_array(null, $header, true)) {
                $cleanHeader = array_map('strval', $header);
                $cleanData = array_map('strval', $data);
                $result = array_combine($cleanHeader, $cleanData);
                $employees[] = $result;
            }
        }

        return $employees;
    }

    /** @return array<int, array<string, mixed>> */
    private function importCsvEmployees(string $filePath): array
    {
        return $this->importExcelEmployees($filePath);
    }

    /** @return array<int, array<string, mixed>> */
    private function importExcelAttendance(string $filePath): array
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (false === $lines || [] === $lines) {
            throw new DataValidationException("无法读取文件或文件为空: {$filePath}");
        }

        $headerLine = array_shift($lines);
        if (false === $headerLine) {
            throw new DataValidationException("文件为空: {$filePath}");
        }

        $header = str_getcsv($headerLine, ',', '"', '');
        $headerCount = count($header);

        $attendanceRecords = [];
        foreach ($lines as $line) {
            $data = str_getcsv($line, ',', '"', '');
            if (count($data) === $headerCount && !in_array(null, $header, true)) {
                $cleanHeader = array_map('strval', $header);
                $cleanData = array_map('strval', $data);
                $result = array_combine($cleanHeader, $cleanData);
                $attendanceRecords[] = $result;
            }
        }

        return $attendanceRecords;
    }

    /** @return array<int, array<string, mixed>> */
    private function importCsvAttendance(string $filePath): array
    {
        return $this->importExcelAttendance($filePath);
    }

    private function validateFileContent(string $filePath, string $type): bool
    {
        $requiredHeaders = match ($type) {
            'employee' => ['员工编号', '姓名', '部门'],
            'attendance' => ['员工编号', '日期'],
            default => [],
        };

        if ([] === $requiredHeaders) {
            return true;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (false === $lines || [] === $lines) {
            return false;
        }

        $header = str_getcsv($lines[0], ',', '"', '');

        foreach ($requiredHeaders as $required) {
            if (!in_array($required, $header, true)) {
                throw new DataValidationException("缺少必需列: {$required}");
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $template
     */
    private function generateTemplate(
        array $template,
        string $format,
    ): string {
        $filename = 'template_' . date('YmdHis') . '.' . $this->getFileExtension($format);
        $exportPath = is_string($this->config['export_path']) ? $this->config['export_path'] : sys_get_temp_dir();
        $filepath = $exportPath . '/' . $filename;

        $headers = is_array($template['headers']) ? $template['headers'] : [];
        $sample = is_array($template['sample']) ? $template['sample'] : [];
        $data = [$headers, $sample];

        $csvData = array_map(fn ($row) => array_values(array_map(fn ($cell) => is_scalar($cell) ? (string) $cell : '', $row)), $data);
        file_put_contents($filepath, $this->arrayToCsv($csvData));

        return $filepath;
    }

    /**
     * @param array<int, array<int, string>> $data
     */
    private function arrayToCsv(
        array $data,
    ): string {
        $output = '';
        foreach ($data as $row) {
            $output .= implode(',', array_map(fn ($field) => '"' . str_replace('"', '""', $field) . '"', $row)) . "\n";
        }

        return $output;
    }

    /**
     * @param array<int, SalaryCalculation> $salaryCalculations
     */
    private function generateSalaryHtml(
        array $salaryCalculations,
    ): string {
        $html = '<html><head><title>薪资报表</title></head><body>';
        $html .= '<h1>薪资报表</h1>';
        $html .= '<table border="1">';
        $html .= '<tr><th>员工编号</th><th>姓名</th><th>部门</th><th>基本工资</th><th>实发工资</th></tr>';

        foreach ($salaryCalculations as $calculation) {
            $employee = $calculation->getEmployee();
            $html .= '<tr>';
            $html .= '<td>' . $employee->getEmployeeNumber() . '</td>';
            $html .= '<td>' . $employee->getName() . '</td>';
            $html .= '<td>' . $employee->getDepartment() . '</td>';
            $html .= '<td>' . $employee->getBaseSalary() . '</td>';
            $html .= '<td>' . $calculation->getNetAmount() . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        return $html;
    }

    /**
     * @param array<int, PaymentRecord> $paymentRecords
     */
    private function generatePayrollHtml(
        array $paymentRecords,
    ): string {
        $html = '<html><head><title>发放报告</title></head><body>';
        $html .= '<h1>薪资发放报告</h1>';
        $html .= '<table border="1">';
        $html .= '<tr><th>员工姓名</th><th>发放金额</th><th>发放方式</th><th>状态</th><th>处理时间</th></tr>';

        foreach ($paymentRecords as $record) {
            $employee = $record->getEmployee();
            $html .= '<tr>';
            $html .= '<td>' . $employee->getName() . '</td>';
            $html .= '<td>' . number_format($record->getAmount(), 2) . '</td>';
            $html .= '<td>' . $record->getMethod()->getLabel() . '</td>';
            $html .= '<td>' . $record->getStatus()->getLabel() . '</td>';
            $html .= '<td>' . $record->getProcessedAt()->format('Y-m-d H:i:s') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        return $html;
    }
}
