<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\ReportData;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Enum\ReportType;
use Tourze\SalaryManageBundle\Exception\ReportGeneratorException;
use Tourze\SalaryManageBundle\Interface\ReportGeneratorInterface;
use Tourze\SalaryManageBundle\Contract\EmployeeRepositoryInterface;
use Tourze\SalaryManageBundle\Service\TaxCalculatorInterface;

class ReportGeneratorService implements ReportGeneratorInterface
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private TaxCalculatorInterface $taxCalculator,
        /** @var array<string, mixed> */
        private array $config = [],
    ) {
        $this->config = array_merge([
            'export_path' => sys_get_temp_dir(),
            'date_format' => 'Y-m-d',
            'currency_format' => '¥%s',
        ], $config);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generatePayrollSummaryReport(
        PayrollPeriod $period,
        array $options = [],
    ): array {
        $employees = $this->getEmployeesForPeriod($period, $options);

        $headers = [
            '员工编号',
            '员工姓名',
            '部门',
            '应发工资',
            '扣除金额',
            '实发工资',
            '发放状态',
        ];

        $data = [];
        $totalGross = 0;
        $totalDeductions = 0;
        $totalNet = 0;

        foreach ($employees as $employee) {
            $salaryCalculation = $this->getSalaryCalculation($employee, $period);

            $grossAmount = $salaryCalculation->getGrossAmount();
            $deductionsAmount = $salaryCalculation->getDeductionsAmount();
            $netAmount = $salaryCalculation->getNetAmount();

            $data[] = [
                'employee_number' => $employee->getEmployeeNumber(),
                'employee_name' => $employee->getName(),
                'department' => $employee->getDepartment(),
                'gross_amount' => $grossAmount,
                'deductions' => $deductionsAmount,
                'net_amount' => $netAmount,
                'payment_status' => '已发放', // 简化处理，实际应查询发放状态
            ];

            $totalGross += $grossAmount;
            $totalDeductions += $deductionsAmount;
            $totalNet += $netAmount;
        }

        $summary = [
            'total_employees' => count($employees),
            'total_gross_amount' => $totalGross,
            'total_deductions' => $totalDeductions,
            'total_net_amount' => $totalNet,
            'average_salary' => count($employees) > 0 ? $totalNet / count($employees) : 0,
        ];

        $reportData = ReportData::create(
            ReportType::PayrollSummary->value,
            ReportType::PayrollSummary->getLabel() . ' - ' . $period->getDisplayName(),
            $period,
            $headers,
            $data,
            $summary,
            array_merge(['generated_by' => 'system'], $options)
        );

        return $reportData->toArray();
    }

    /**
     * @param array<int, Employee> $employees
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generateTaxReport(
        PayrollPeriod $period,
        array $employees = [],
        array $options = [],
    ): array {
        if ([] === $employees) {
            $employees = $this->getEmployeesForPeriod($period, $options);
        }

        $headers = [
            '员工编号',
            '员工姓名',
            '身份证号',
            '应税收入',
            '专项扣除',
            '专项附加扣除',
            '应纳税所得额',
            '税率',
            '应纳税额',
            '累计已缴税额',
            '本期应缴税额',
        ];

        $data = [];
        $totalTaxableIncome = 0;
        $totalTaxAmount = 0;
        $totalCurrentTax = 0;

        foreach ($employees as $employee) {
            $salaryCalculation = $this->getSalaryCalculation($employee, $period);
            $taxResult = $this->taxCalculator->calculate($employee, $salaryCalculation->getGrossAmount());

            $taxableIncome = $taxResult->getTaxableIncome();
            $taxAmount = $taxResult->getTaxAmount();
            $currentTax = $taxResult->getCurrentTax();

            $data[] = [
                'employee_number' => $employee->getEmployeeNumber(),
                'employee_name' => $employee->getName(),
                'id_number' => $this->maskIdNumber($employee->getIdNumber() ?? ''),
                'taxable_income' => $taxableIncome,
                'basic_deduction' => $taxResult->getBasicDeduction(),
                'additional_deduction' => $taxResult->getAdditionalDeduction(),
                'taxable_amount' => $taxResult->getTaxableAmount(),
                'tax_rate' => $taxResult->getTaxRate() . '%',
                'tax_amount' => $taxAmount,
                'cumulative_tax' => $taxResult->getCumulativeTax(),
                'current_tax' => $currentTax,
            ];

            $totalTaxableIncome += $taxableIncome;
            $totalTaxAmount += $taxAmount;
            $totalCurrentTax += $currentTax;
        }

        $summary = [
            'total_employees' => count($employees),
            'total_taxable_income' => $totalTaxableIncome,
            'total_tax_amount' => $totalTaxAmount,
            'total_current_tax' => $totalCurrentTax,
            'average_tax_rate' => $totalTaxableIncome > 0 ? ($totalTaxAmount / $totalTaxableIncome * 100) : 0,
        ];

        $reportData = ReportData::create(
            ReportType::TaxReport->value,
            ReportType::TaxReport->getLabel() . ' - ' . $period->getDisplayName(),
            $period,
            $headers,
            $data,
            $summary,
            array_merge(['tax_period' => $period->getKey()], $options)
        );

        return $reportData->toArray();
    }

    /**
     * @param array<int, Employee> $employees
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generateSocialInsuranceReport(
        PayrollPeriod $period,
        array $employees = [],
        array $options = [],
    ): array {
        if ([] === $employees) {
            $employees = $this->getEmployeesForPeriod($period, $options);
        }

        $headers = [
            '员工编号',
            '员工姓名',
            '缴费基数',
            '养老保险',
            '医疗保险',
            '失业保险',
            '工伤保险',
            '生育保险',
            '住房公积金',
            '个人合计',
            '企业合计',
            '总计',
        ];

        $data = [];
        $totalContributionBase = 0;
        $totalPersonalContribution = 0;
        $totalCompanyContribution = 0;
        $insuranceTotals = [
            'pension' => 0,
            'medical' => 0,
            'unemployment' => 0,
            'work_injury' => 0,
            'maternity' => 0,
            'housing_fund' => 0,
        ];

        foreach ($employees as $employee) {
            $salaryCalculation = $this->getSalaryCalculation($employee, $period);

            // 简化处理，创建一个模拟的社保结果
            $contributionBase = 10000.0; // 简化的缴费基数
            $personalTotal = 1100.0;     // 简化的个人总缴费
            $companyTotal = 3100.0;      // 简化的企业总缴费

            $data[] = [
                'employee_number' => $employee->getEmployeeNumber(),
                'employee_name' => $employee->getName(),
                'contribution_base' => $contributionBase,
                'pension_insurance' => 800.0,         // 简化的各项保险
                'medical_insurance' => 200.0,
                'unemployment_insurance' => 50.0,
                'work_injury_insurance' => 80.0,
                'maternity_insurance' => 70.0,
                'housing_fund' => 1200.0,
                'personal_total' => $personalTotal,
                'company_total' => $companyTotal,
                'total_contribution' => $personalTotal + $companyTotal,
            ];

            $totalContributionBase += $contributionBase;
            $totalPersonalContribution += $personalTotal;
            $totalCompanyContribution += $companyTotal;

            // 简化的保险项目统计
            $insuranceTotals['pension'] += 800.0;
            $insuranceTotals['medical'] += 200.0;
            $insuranceTotals['unemployment'] += 50.0;
            $insuranceTotals['work_injury'] += 80.0;
            $insuranceTotals['maternity'] += 70.0;
            $insuranceTotals['housing_fund'] += 1200.0;
        }

        $summary = [
            'total_employees' => count($employees),
            'total_contribution_base' => $totalContributionBase,
            'total_personal_contribution' => $totalPersonalContribution,
            'total_company_contribution' => $totalCompanyContribution,
            'total_contribution' => $totalPersonalContribution + $totalCompanyContribution,
            'insurance_breakdown' => $insuranceTotals,
            'average_contribution_base' => count($employees) > 0 ? $totalContributionBase / count($employees) : 0,
        ];

        $reportData = ReportData::create(
            ReportType::SocialInsuranceReport->value,
            ReportType::SocialInsuranceReport->getLabel() . ' - ' . $period->getDisplayName(),
            $period,
            $headers,
            $data,
            $summary,
            array_merge(['region' => 'default'], $options)
        );

        return $reportData->toArray();
    }

    /**
     * @param array<int, Employee> $employees
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generateIndividualTaxReport(
        PayrollPeriod $period,
        array $employees = [],
        array $options = [],
    ): array {
        if ([] === $employees) {
            $employees = $this->getEmployeesForPeriod($period, $options);
        }

        $headers = [
            '员工编号',
            '员工姓名',
            '月度收入',
            '累计收入',
            '累计基本扣除',
            '累计专项扣除',
            '累计专项附加扣除',
            '累计应纳税所得额',
            '税率',
            '累计应纳税额',
            '累计已缴税额',
            '本月应缴税额',
        ];

        $data = [];
        $totalMonthlyIncome = 0;
        $totalCumulativeIncome = 0;
        $totalCumulativeTax = 0;
        $totalCurrentTax = 0;

        foreach ($employees as $employee) {
            $salaryCalculation = $this->getSalaryCalculation($employee, $period);
            $taxResult = $this->taxCalculator->calculate($employee, $salaryCalculation->getGrossAmount());

            $monthlyIncome = $salaryCalculation->getGrossAmount();
            $cumulativeIncome = $taxResult->getCumulativeIncome();
            $cumulativeTax = $taxResult->getCumulativeTax();
            $currentTax = $taxResult->getCurrentTax();

            $data[] = [
                'employee_number' => $employee->getEmployeeNumber(),
                'employee_name' => $employee->getName(),
                'monthly_income' => $monthlyIncome,
                'cumulative_income' => $cumulativeIncome,
                'cumulative_basic_deduction' => $taxResult->getCumulativeBasicDeduction(),
                'cumulative_special_deduction' => $taxResult->getCumulativeSpecialDeduction(),
                'cumulative_additional_deduction' => $taxResult->getCumulativeAdditionalDeduction(),
                'cumulative_taxable_amount' => $taxResult->getCumulativeTaxableAmount(),
                'tax_rate' => $taxResult->getTaxRate() . '%',
                'cumulative_tax_amount' => $taxResult->getCumulativeTaxAmount(),
                'cumulative_paid_tax' => $cumulativeTax,
                'current_tax' => $currentTax,
            ];

            $totalMonthlyIncome += $monthlyIncome;
            $totalCumulativeIncome += $cumulativeIncome;
            $totalCumulativeTax += $cumulativeTax;
            $totalCurrentTax += $currentTax;
        }

        $summary = [
            'total_employees' => count($employees),
            'total_monthly_income' => $totalMonthlyIncome,
            'total_cumulative_income' => $totalCumulativeIncome,
            'total_cumulative_tax' => $totalCumulativeTax,
            'total_current_tax' => $totalCurrentTax,
            'average_monthly_income' => count($employees) > 0 ? $totalMonthlyIncome / count($employees) : 0,
        ];

        $reportData = ReportData::create(
            ReportType::IndividualTaxReport->value,
            ReportType::IndividualTaxReport->getLabel() . ' - ' . $period->getDisplayName(),
            $period,
            $headers,
            $data,
            $summary,
            array_merge(['calculation_method' => 'cumulative'], $options)
        );

        return $reportData->toArray();
    }

    /**
     * @param array<string, mixed> $reportData
     * @param array<string, mixed> $options
     */
    public function exportReport(
        array $reportData,
        string $format,
        array $options = [],
    ): string {
        $this->validateFormat($format);

        $reportType = is_string($reportData['report_type'] ?? null) ? $reportData['report_type'] : 'report';
        $period = is_string($reportData['period'] ?? null) ? $reportData['period'] : date('Ym');
        $filename = $this->generateFilename($reportType, $period, $format);
        $exportPath = is_string($this->config['export_path']) ? $this->config['export_path'] : sys_get_temp_dir();
        $filepath = $exportPath . '/' . $filename;

        return match (strtolower($format)) {
            'excel' => $this->exportToExcel($reportData, $filepath, $options),
            'csv' => $this->exportToCsv($reportData, $filepath, $options),
            'pdf' => $this->exportToPdf($reportData, $filepath, $options),
            'json' => $this->exportToJson($reportData, $filepath, $options),
            default => throw new ReportGeneratorException("不支持的导出格式: {$format}"),
        };
    }

    /** @return array<int, string> */
    public function getSupportedFormats(): array
    {
        return ['excel', 'csv', 'pdf', 'json'];
    }

    /** @return array<string, string> */
    public function getReportTypes(): array
    {
        return [
            ReportType::PayrollSummary->value => ReportType::PayrollSummary->getLabel(),
            ReportType::TaxReport->value => ReportType::TaxReport->getLabel(),
            ReportType::SocialInsuranceReport->value => ReportType::SocialInsuranceReport->getLabel(),
            ReportType::IndividualTaxReport->value => ReportType::IndividualTaxReport->getLabel(),
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return array<int, Employee>
     */
    private function getEmployeesForPeriod(
        PayrollPeriod $period,
        array $options = [],
    ): array {
        if (isset($options['employees']) && is_array($options['employees'])) {
            /** @var array<int, Employee> */
            return array_values(array_filter($options['employees'], fn ($e) => $e instanceof Employee));
        }

        if (isset($options['department']) && is_string($options['department'])) {
            return array_values($this->employeeRepository->findByDepartment($options['department']));
        }

        return array_values($this->employeeRepository->findActiveEmployees());
    }

    private function getSalaryCalculation(Employee $employee, PayrollPeriod $period): SalaryCalculation
    {
        // 在实际应用中，这里应该从数据库或缓存中获取已计算的薪资数据
        // 这里创建一个简单的模拟对象
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($employee);
        $calculation->setPeriod($period);

        return $calculation;
    }

    private function maskIdNumber(string $idNumber): string
    {
        if (strlen($idNumber) < 8) {
            return $idNumber;
        }

        return substr($idNumber, 0, 6) . '******' . substr($idNumber, -4);
    }

    private function validateFormat(string $format): void
    {
        if (!in_array(strtolower($format), $this->getSupportedFormats(), true)) {
            throw new ReportGeneratorException("不支持的导出格式: {$format}");
        }
    }

    private function generateFilename(string $reportType, string $period, string $format): string
    {
        $timestamp = date('YmdHis');
        $extension = match (strtolower($format)) {
            'excel' => 'xlsx',
            'csv' => 'csv',
            'pdf' => 'pdf',
            'json' => 'json',
            default => $format,
        };

        return "report_{$reportType}_{$period}_{$timestamp}.{$extension}";
    }

    /**
     * @param array<string, mixed> $reportData
     * @param array<string, mixed> $options
     */
    private function exportToExcel(
        array $reportData,
        string $filepath,
        array $options = [],
    ): string {
        return $this->exportToCsvFormat($reportData, $filepath);
    }

    /**
     * @param array<string, mixed> $reportData
     * @param array<string, mixed> $options
     */
    private function exportToCsv(
        array $reportData,
        string $filepath,
        array $options = [],
    ): string {
        return $this->exportToCsvFormat($reportData, $filepath);
    }

    /**
     * @param array<string, mixed> $reportData
     */
    private function exportToCsvFormat(array $reportData, string $filepath): string
    {
        $csvData = $this->convertReportDataToCsvArray($reportData);
        $content = $this->arrayToCsv($csvData);
        file_put_contents($filepath, $content);

        return $filepath;
    }

    /**
     * @param array<string, mixed> $reportData
     * @return array<int, array<int, string>>
     */
    private function convertReportDataToCsvArray(array $reportData): array
    {
        $headers = is_array($reportData['headers'] ?? null) ? $reportData['headers'] : [];
        $data = is_array($reportData['data'] ?? null) ? $reportData['data'] : [];

        $headerRow = $this->convertHeadersToCsvRow($headers);
        $dataRows = $this->convertDataToCsvRows($data);

        return array_merge([$headerRow], $dataRows);
    }

    /**
     * @param array<int|string, mixed> $headers
     * @return array<int, string>
     */
    private function convertHeadersToCsvRow(array $headers): array
    {
        return array_values(array_map(fn ($h) => is_scalar($h) ? (string) $h : '', $headers));
    }

    /**
     * @param array<int|string, mixed> $data
     * @return array<int, array<int, string>>
     */
    private function convertDataToCsvRows(array $data): array
    {
        return array_values(array_map(fn ($row) => $this->convertRowToCsvRow($row), $data));
    }

    /**
     * @param mixed $row
     * @return array<int, string>
     */
    private function convertRowToCsvRow(mixed $row): array
    {
        if (!is_array($row)) {
            return [];
        }

        return array_values(array_map(fn ($cell) => is_scalar($cell) ? (string) $cell : '', $row));
    }

    /**
     * @param array<string, mixed> $reportData
     * @param array<string, mixed> $options
     */
    private function exportToPdf(
        array $reportData,
        string $filepath,
        array $options = [],
    ): string {
        $html = $this->generateReportHtml($reportData);
        file_put_contents($filepath, $html);

        return $filepath;
    }

    /**
     * @param array<string, mixed> $reportData
     * @param array<string, mixed> $options
     */
    private function exportToJson(
        array $reportData,
        string $filepath,
        array $options = [],
    ): string {
        $content = json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filepath, $content);

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
     * @param array<string, mixed> $reportData
     */
    private function generateReportHtml(
        array $reportData,
    ): string {
        return $this->buildHtmlHeader($reportData)
            . $this->buildReportMetadata($reportData)
            . $this->buildDataTable($reportData)
            . $this->buildSummaryTable($reportData)
            . '</body></html>';
    }

    /**
     * @param array<string, mixed> $reportData
     */
    private function buildHtmlHeader(array $reportData): string
    {
        $title = is_string($reportData['title'] ?? null) ? $reportData['title'] : '报表';

        return '<html><head>'
            . '<meta charset="utf-8">'
            . '<title>' . htmlspecialchars($title) . '</title>'
            . '<style>table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }</style>'
            . '</head><body>';
    }

    /**
     * @param array<string, mixed> $reportData
     */
    private function buildReportMetadata(array $reportData): string
    {
        $title = is_string($reportData['title'] ?? null) ? $reportData['title'] : '报表';
        $generatedAt = is_string($reportData['generated_at'] ?? null) ? $reportData['generated_at'] : date('Y-m-d H:i:s');
        $totalRows = is_int($reportData['total_rows'] ?? null) ? (string) $reportData['total_rows'] : '0';

        return '<h1>' . htmlspecialchars($title) . '</h1>'
            . '<p>生成时间: ' . htmlspecialchars($generatedAt) . '</p>'
            . '<p>数据行数: ' . htmlspecialchars($totalRows) . '</p>';
    }

    /**
     * @param array<string, mixed> $reportData
     */
    private function buildDataTable(array $reportData): string
    {
        $headers = is_array($reportData['headers'] ?? null) ? $reportData['headers'] : [];
        $data = is_array($reportData['data'] ?? null) ? $reportData['data'] : [];

        return '<table>'
            . $this->buildTableHeader($headers)
            . $this->buildTableBody($data)
            . '</table>';
    }

    /**
     * @param array<int|string, mixed> $headers
     */
    private function buildTableHeader(array $headers): string
    {
        $html = '<thead><tr>';
        foreach ($headers as $header) {
            $headerText = is_string($header) ? $header : '';
            $html .= '<th>' . htmlspecialchars($headerText) . '</th>';
        }

        return $html . '</tr></thead>';
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function buildTableBody(array $data): string
    {
        $html = '<tbody>';
        foreach ($data as $row) {
            $html .= $this->buildTableRow($row);
        }

        return $html . '</tbody>';
    }

    /**
     * @param mixed $row
     */
    private function buildTableRow(mixed $row): string
    {
        if (!is_array($row)) {
            return '';
        }

        $html = '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars($this->formatCellValue($cell)) . '</td>';
        }

        return $html . '</tr>';
    }

    /**
     * @param mixed $cell
     */
    private function formatCellValue(mixed $cell): string
    {
        if (is_numeric($cell)) {
            return number_format((float) $cell, 2);
        }

        return is_string($cell) ? $cell : '';
    }

    /**
     * @param array<string, mixed> $reportData
     */
    private function buildSummaryTable(array $reportData): string
    {
        $summary = $reportData['summary'] ?? null;
        if (!is_array($summary) || [] === $summary) {
            return '';
        }

        /** @var array<string, mixed> $validSummary */
        $validSummary = array_filter(
            $summary,
            fn ($key) => is_string($key),
            \ARRAY_FILTER_USE_KEY
        );

        return '<h2>汇总信息</h2><table>'
            . $this->buildSummaryRows($validSummary)
            . '</table>';
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function buildSummaryRows(array $summary): string
    {
        $html = '';
        foreach ($summary as $key => $value) {
            $html .= $this->buildSummaryRow($key, $value);
        }

        return $html;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    private function buildSummaryRow(mixed $key, mixed $value): string
    {
        $keyText = is_string($key) ? $key : '';
        $valueText = $this->formatSummaryValue($value);

        return '<tr>'
            . '<td><strong>' . htmlspecialchars($keyText) . '</strong></td>'
            . '<td>' . htmlspecialchars($valueText) . '</td>'
            . '</tr>';
    }

    /**
     * @param mixed $value
     */
    private function formatSummaryValue(mixed $value): string
    {
        if (is_numeric($value)) {
            return number_format((float) $value, 2);
        }

        return is_string($value) ? $value : '';
    }
}
