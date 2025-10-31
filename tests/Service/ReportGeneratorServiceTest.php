<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\ContributionBase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SocialInsuranceResult;
use Tourze\SalaryManageBundle\Entity\TaxResult;
use Tourze\SalaryManageBundle\Exception\ReportGeneratorException;
use Tourze\SalaryManageBundle\Interface\SocialInsuranceCalculatorInterface;
use Tourze\SalaryManageBundle\Repository\EmployeeRepository;
use Tourze\SalaryManageBundle\Service\ReportGeneratorService;
use Tourze\SalaryManageBundle\Service\TaxCalculatorInterface;
use Tourze\SalaryManageBundle\Tests\Helper\MockEmployeeRepository;
use Tourze\SalaryManageBundle\Tests\Helper\MockTaxCalculatorInterface;

/**
 * @internal
 */
#[CoversClass(ReportGeneratorService::class)]
class ReportGeneratorServiceTest extends TestCase
{
    private ReportGeneratorService $reportGenerator;

    private MockEmployeeRepository $employeeRepository;

    private MockTaxCalculatorInterface $taxCalculator;

    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/test_reports';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0o777, true);
        }

        $this->employeeRepository = new MockEmployeeRepository();

        $this->taxCalculator = new MockTaxCalculatorInterface();

        $this->reportGenerator = new ReportGeneratorService(
            $this->employeeRepository,
            $this->taxCalculator,
            ['export_path' => $this->tempDir]
        );
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            if (false !== $files) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testGeneratePayrollSummaryReport(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $employees = [$this->createMockEmployee()];

        $this->employeeRepository->expectCall('findActiveEmployees', 1);
        $this->employeeRepository->setActiveEmployees($employees);

        $result = $this->reportGenerator->generatePayrollSummaryReport($period);

        $this->assertIsArray($result);
        $this->assertEquals('payroll_summary', $result['report_type']);
        $title = $result['title'];
        $this->assertIsString($title);
        $this->assertStringContainsString('薪资发放汇总报告', $title);
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('summary', $result);

        $headers = $result['headers'];
        $this->assertTrue(is_array($headers) || $headers instanceof \Countable, 'Headers should be array or Countable');
        $this->assertCount(7, $headers); // 7个表头字段
        $summary = $result['summary'];
        $this->assertIsArray($summary);
        $totalEmployees = $summary['total_employees'];
        $this->assertEquals(1, $totalEmployees);
        $totalRows = $result['total_rows'];
        $this->assertIsInt($totalRows);
        $this->assertGreaterThan(0, $totalRows);
        $this->employeeRepository->verifyExpectedCalls();
    }

    public function testGenerateTaxReport(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $employees = [$this->createMockEmployee()];

        $taxResult = $this->createMockTaxResult();
        $this->taxCalculator->expectCall('calculate', 1);
        $this->taxCalculator->setCalculateResult($taxResult);

        $result = $this->reportGenerator->generateTaxReport($period, $employees);

        $this->assertIsArray($result);
        $this->assertEquals('tax_report', $result['report_type']);
        $title = $result['title'];
        $this->assertIsString($title);
        $this->assertStringContainsString('个税申报报告', $title);
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('summary', $result);

        $headers = $result['headers'];
        $this->assertTrue(is_array($headers) || $headers instanceof \Countable, 'Headers should be array or Countable');
        $this->assertCount(11, $headers); // 11个表头字段
        $summary = $result['summary'];
        $this->assertIsArray($summary);
        $this->assertEquals(1, $summary['total_employees']);
        $this->assertArrayHasKey('total_taxable_income', $summary);
        $this->assertArrayHasKey('total_tax_amount', $summary);
        $this->taxCalculator->verifyExpectedCalls();
    }

    public function testGenerateSocialInsuranceReport(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $employees = [$this->createMockEmployee()];

        $result = $this->reportGenerator->generateSocialInsuranceReport($period, $employees);

        $this->assertIsArray($result);
        $this->assertEquals('social_insurance_report', $result['report_type']);
        $title = $result['title'];
        $this->assertIsString($title);
        $this->assertStringContainsString('社保缴费汇总报告', $title);
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('summary', $result);

        $headers = $result['headers'];
        $this->assertTrue(is_array($headers) || $headers instanceof \Countable, 'Headers should be array or Countable');
        $this->assertCount(12, $headers); // 12个表头字段
        $summary = $result['summary'];
        $this->assertIsArray($summary);
        $this->assertEquals(1, $summary['total_employees']);
        $this->assertArrayHasKey('total_contribution_base', $summary);
        $this->assertArrayHasKey('insurance_breakdown', $summary);
    }

    public function testGenerateIndividualTaxReport(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $employees = [$this->createMockEmployee()];

        $taxResult = $this->createMockTaxResult();
        $this->taxCalculator->expectCall('calculate', 1);
        $this->taxCalculator->setCalculateResult($taxResult);

        $result = $this->reportGenerator->generateIndividualTaxReport($period, $employees);

        $this->assertIsArray($result);
        $this->assertEquals('individual_tax_report', $result['report_type']);
        $title = $result['title'];
        $this->assertIsString($title);
        $this->assertStringContainsString('个人所得税报告', $title);
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('summary', $result);

        $headers = $result['headers'];
        $this->assertTrue(is_array($headers) || $headers instanceof \Countable, 'Headers should be array or Countable');
        $this->assertCount(12, $headers); // 12个表头字段
        $summary = $result['summary'];
        $this->assertIsArray($summary);
        $this->assertEquals(1, $summary['total_employees']);
        $this->assertArrayHasKey('total_monthly_income', $summary);
        $this->assertArrayHasKey('total_cumulative_tax', $summary);
        $this->taxCalculator->verifyExpectedCalls();
    }

    public function testExportReportToExcel(): void
    {
        $reportData = $this->createMockReportData();

        $filepath = $this->reportGenerator->exportReport($reportData, 'excel');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.xlsx', $filepath);
        $this->assertStringContainsString('report_', basename($filepath));

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content, 'Failed to read file');
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('员工编号', $content);
        $this->assertStringContainsString('张三', $content);
    }

    public function testExportReportToCsv(): void
    {
        $reportData = $this->createMockReportData();

        $filepath = $this->reportGenerator->exportReport($reportData, 'csv');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.csv', $filepath);

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content, 'Failed to read file');
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('员工编号', $content);
        $this->assertStringContainsString('张三', $content);
    }

    public function testExportReportToPdf(): void
    {
        $reportData = $this->createMockReportData();

        $filepath = $this->reportGenerator->exportReport($reportData, 'pdf');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.pdf', $filepath);

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content, 'Failed to read file');
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('<html>', $content);
        $this->assertStringContainsString('薪资发放汇总报告', $content);
        $this->assertStringContainsString('张三', $content);
    }

    public function testExportReportToJson(): void
    {
        $reportData = $this->createMockReportData();

        $filepath = $this->reportGenerator->exportReport($reportData, 'json');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.json', $filepath);

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content);
        $decodedData = json_decode($content, true);

        $this->assertIsArray($decodedData);
        $this->assertEquals('payroll_summary', $decodedData['report_type']);
        $this->assertArrayHasKey('data', $decodedData);
    }

    public function testExportReportWithUnsupportedFormat(): void
    {
        $reportData = $this->createMockReportData();

        $this->expectException(ReportGeneratorException::class);
        $this->expectExceptionMessage('不支持的导出格式: xml');

        $this->reportGenerator->exportReport($reportData, 'xml');
    }

    public function testGetSupportedFormats(): void
    {
        $formats = $this->reportGenerator->getSupportedFormats();

        $this->assertIsArray($formats);
        $this->assertContains('excel', $formats);
        $this->assertContains('csv', $formats);
        $this->assertContains('pdf', $formats);
        $this->assertContains('json', $formats);
    }

    public function testGetReportTypes(): void
    {
        $types = $this->reportGenerator->getReportTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey('payroll_summary', $types);
        $this->assertArrayHasKey('tax_report', $types);
        $this->assertArrayHasKey('social_insurance_report', $types);
        $this->assertArrayHasKey('individual_tax_report', $types);

        $this->assertEquals('薪资发放汇总报告', $types['payroll_summary']);
        $this->assertEquals('个税申报报告', $types['tax_report']);
    }

    public function testGeneratePayrollSummaryReportWithDepartmentFilter(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $employees = [$this->createMockEmployee()];

        $this->employeeRepository->expectCall('findByDepartment', 1);
        $this->employeeRepository->setDepartmentEmployees('技术部', $employees);

        $result = $this->reportGenerator->generatePayrollSummaryReport($period, ['department' => '技术部']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('report_type', $result);
        $summary = $result['summary'];
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_employees', $summary);
        $this->assertEquals(1, $summary['total_employees']);
        $this->assertEquals('payroll_summary', $result['report_type']);
        $this->employeeRepository->verifyExpectedCalls();
    }

    public function testGenerateReportWithCustomEmployeeList(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $employees = [
            $this->createMockEmployee('E001', '张三'),
            $this->createMockEmployee('E002', '李四'),
        ];

        $result = $this->reportGenerator->generatePayrollSummaryReport($period, ['employees' => $employees]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('data', $result);
        $summary = $result['summary'];
        $data = $result['data'];
        $this->assertIsArray($summary);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('total_employees', $summary);
        $this->assertEquals(2, $summary['total_employees']);
        $this->assertCount(2, $data);
    }

    private function createMockEmployee(string $number = 'E001', string $name = '张三'): Employee
    {
        $employee = new Employee();
        $employee->setEmployeeNumber($number);
        $employee->setName($name);
        $employee->setDepartment('技术部');
        $employee->setBaseSalary('10000.00');
        $employee->setIdNumber('110101199001011234');
        $employee->setHireDate(new \DateTimeImmutable('2023-01-01'));

        return $employee;
    }

    private function createMockTaxResult(): TaxResult
    {
        $employee = $this->createMockEmployee();
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);

        return new TaxResult(
            employee: $employee,
            period: $period,
            grossIncome: 10000.0,
            taxableIncome: 8500.0,
            taxAmount: 290.0,
            netIncome: 9710.0,
            deductions: [],
            taxCalculationDetails: [],
            metadata: [],
            basicDeduction: 5000.0,
            additionalDeduction: 1500.0,
            taxableAmount: 2000.0,
            taxRate: 3.0,
            cumulativeTax: 1200.0,
            currentTax: 290.0,
            cumulativeIncome: 85000.0,
            cumulativeBasicDeduction: 50000.0,
            cumulativeSpecialDeduction: 15000.0,
            cumulativeAdditionalDeduction: 15000.0,
            cumulativeTaxableAmount: 5000.0,
            cumulativeTaxAmount: 1500.0
        );
    }

    /** @return array<string, mixed> */
    private function createMockReportData(): array
    {
        return [
            'report_type' => 'payroll_summary',
            'title' => '薪资发放汇总报告 - 2025年1月',
            'period' => '2025-01',
            'headers' => ['员工编号', '员工姓名', '部门', '应发工资', '扣除金额', '实发工资', '发放状态'],
            'data' => [
                [
                    'employee_number' => 'E001',
                    'employee_name' => '张三',
                    'department' => '技术部',
                    'gross_amount' => 10000.0,
                    'deductions' => 1500.0,
                    'net_amount' => 8500.0,
                    'payment_status' => '已发放',
                ],
            ],
            'summary' => [
                'total_employees' => 1,
                'total_gross_amount' => 10000.0,
                'total_deductions' => 1500.0,
                'total_net_amount' => 8500.0,
            ],
            'total_rows' => 1,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
