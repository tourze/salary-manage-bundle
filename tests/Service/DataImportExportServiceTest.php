<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PaymentRecord;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Enum\PaymentMethod;
use Tourze\SalaryManageBundle\Enum\PaymentStatus;
use Tourze\SalaryManageBundle\Service\DataImportExportService;

/**
 * @internal
 */
#[CoversClass(DataImportExportService::class)]
class DataImportExportServiceTest extends TestCase
{
    private DataImportExportService $service;

    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/test_export';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0o777, true);
        }

        $this->service = new DataImportExportService([
            'export_path' => $this->tempDir,
        ]);
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

    public function testExportSalaryDataToCsv(): void
    {
        $salaryCalculations = [$this->createMockSalaryCalculation()];

        $filepath = $this->service->exportSalaryData($salaryCalculations, 'csv');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.csv', $filepath);

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content, 'Failed to read CSV file');
        $this->assertStringContainsString('员工编号', $content);
        $this->assertStringContainsString('张三', $content);
        $this->assertStringContainsString('E001', $content);
    }

    public function testExportSalaryDataToExcel(): void
    {
        $salaryCalculations = [$this->createMockSalaryCalculation()];

        $filepath = $this->service->exportSalaryData($salaryCalculations, 'excel');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.xlsx', $filepath);
    }

    public function testExportSalaryDataToPdf(): void
    {
        $salaryCalculations = [$this->createMockSalaryCalculation()];

        $filepath = $this->service->exportSalaryData($salaryCalculations, 'pdf');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.pdf', $filepath);

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content, 'Failed to read PDF file');
        $this->assertStringContainsString('<html>', $content);
        $this->assertStringContainsString('薪资报表', $content);
    }

    public function testExportSalaryDataWithUnsupportedFormat(): void
    {
        $salaryCalculations = [$this->createMockSalaryCalculation()];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('不支持的格式: xml');

        $this->service->exportSalaryData($salaryCalculations, 'xml');
    }

    public function testExportPayrollReportToPdf(): void
    {
        $paymentRecords = [$this->createMockPaymentRecord()];

        $filepath = $this->service->exportPayrollReport($paymentRecords, 'pdf');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.pdf', $filepath);

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content, 'Failed to read payroll report PDF file');
        $this->assertStringContainsString('薪资发放报告', $content);
        $this->assertStringContainsString('张三', $content);
    }

    public function testGetSupportedFormats(): void
    {
        $formats = $this->service->getSupportedFormats();

        $this->assertIsArray($formats);
        $this->assertArrayHasKey('export', $formats);
        $this->assertArrayHasKey('import', $formats);
        $this->assertArrayHasKey('extensions', $formats);

        $this->assertContains('excel', $formats['export']);
        $this->assertContains('csv', $formats['export']);
        $this->assertContains('pdf', $formats['export']);
    }

    public function testGetImportTemplate(): void
    {
        $filepath = $this->service->getImportTemplate('employee');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.xlsx', $filepath);

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content, 'Failed to read import template file');
        $this->assertStringContainsString('员工编号', $content);
        $this->assertStringContainsString('姓名', $content);
        $this->assertStringContainsString('部门', $content);
    }

    public function testGetImportTemplateWithUnsupportedType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('不支持的模板类型: invalid');

        $this->service->getImportTemplate('invalid');
    }

    public function testImportEmployeeData(): void
    {
        $csvData = "员工编号,姓名,部门,岗位,基本工资,入职日期\n";
        $csvData .= "E001,张三,技术部,工程师,10000,2023-01-01\n";
        $csvData .= "E002,李四,销售部,经理,12000,2022-06-01\n";

        $filepath = $this->tempDir . '/employees.csv';
        file_put_contents($filepath, $csvData);

        $result = $this->service->importEmployeeData($filepath);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('E001', $result[0]['员工编号']);
        $this->assertEquals('张三', $result[0]['姓名']);
        $this->assertEquals('技术部', $result[0]['部门']);
    }

    public function testImportAttendanceData(): void
    {
        $csvData = "员工编号,日期,签到时间,签退时间,工作小时,加班小时\n";
        $csvData .= "E001,2025-01-15,09:00,18:00,8,1\n";
        $csvData .= "E001,2025-01-16,09:00,17:30,7.5,0\n";

        $filepath = $this->tempDir . '/attendance.csv';
        file_put_contents($filepath, $csvData);

        $result = $this->service->importAttendanceData($filepath);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('E001', $result[0]['员工编号']);
        $this->assertEquals('2025-01-15', $result[0]['日期']);
        $this->assertEquals('09:00', $result[0]['签到时间']);
    }

    public function testValidateImportFileWithNonExistentFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('文件不存在');

        $this->service->validateImportFile('/non/existent/file.csv', 'employee');
    }

    public function testValidateImportFileWithUnsupportedExtension(): void
    {
        $filepath = $this->tempDir . '/test.txt';
        file_put_contents($filepath, 'test content');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('不支持的文件格式: txt');

        $this->service->validateImportFile($filepath, 'employee');
    }

    public function testValidateImportFileWithMissingRequiredHeaders(): void
    {
        $csvData = "编号,名称\nE001,张三\n";
        $filepath = $this->tempDir . '/invalid.csv';
        file_put_contents($filepath, $csvData);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('缺少必需列: 员工编号');

        $this->service->validateImportFile($filepath, 'employee');
    }

    public function testValidateImportFileSuccess(): void
    {
        $csvData = "员工编号,姓名,部门\nE001,张三,技术部\n";
        $filepath = $this->tempDir . '/valid.csv';
        file_put_contents($filepath, $csvData);

        $result = $this->service->validateImportFile($filepath, 'employee');

        $this->assertTrue($result);
    }

    public function testImportDataWithUnsupportedFileFormat(): void
    {
        $filepath = $this->tempDir . '/test.txt';
        file_put_contents($filepath, 'test content');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('不支持的文件格式: txt');

        $this->service->importEmployeeData($filepath);
    }

    private function createMockSalaryCalculation(): SalaryCalculation
    {
        $employee = $this->createMockEmployee();
        $period = $this->createMockPayrollPeriod();

        $calculation = new SalaryCalculation();
        $calculation->setEmployee($employee);
        $calculation->setPeriod($period);

        return $calculation;
    }

    private function createMockEmployee(): Employee
    {
        $employee = new Employee();
        $employee->setEmployeeNumber('E001');
        $employee->setName('张三');
        $employee->setDepartment('技术部');
        $employee->setBaseSalary('10000.00');
        $employee->setHireDate(new \DateTimeImmutable('2023-01-01'));

        return $employee;
    }

    private function createMockPayrollPeriod(): PayrollPeriod
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        return $period;
    }

    private function createMockPaymentRecord(): PaymentRecord
    {
        $employee = $this->createMockEmployee();
        $salaryCalculation = $this->createMockSalaryCalculation();
        $period = $this->createMockPayrollPeriod();

        return new PaymentRecord(
            paymentId: 'PAY_TEST_123',
            employee: $employee,
            salaryCalculation: $salaryCalculation,
            period: $period,
            amount: 8500.0,
            method: PaymentMethod::BankTransfer,
            status: PaymentStatus::Success,
            processedAt: new \DateTimeImmutable(),
        );
    }
}
