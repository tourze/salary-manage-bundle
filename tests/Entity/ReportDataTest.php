<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\ReportData;

/**
 * @internal
 */
#[CoversClass(ReportData::class)]
final class ReportDataTest extends AbstractEntityTestCase
{
    private PayrollPeriod $period;

    protected function setUp(): void
    {
        $this->period = new PayrollPeriod();
        $this->period->setYear(2025);
        $this->period->setMonth(1);
    }

    protected function createEntity(): ReportData
    {
        return ReportData::create(
            'salary_summary',
            '测试报表',
            $this->period,
            ['test'],
            [['test' => 'data']],
            []
        );
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);

        return [
            'reportType' => ['reportType', 'monthly_report'],
            'title' => ['title', '月度薪资报表'],
            'period' => ['period', $period],
            'headers' => ['headers', ['员工编号', '姓名', '基本工资']],
            'data' => ['data', [
                ['employee_number' => 'EMP001', 'name' => '张三', 'base_salary' => 10000],
                ['employee_number' => 'EMP002', 'name' => '李四', 'base_salary' => 12000],
            ]],
            'summary' => ['summary', ['total_employees' => 2, 'total_base_salary' => 22000]],
            'metadata' => ['metadata', ['version' => '1.0', 'format' => 'excel']],
            'generatedAt' => ['generatedAt', new \DateTimeImmutable('2025-01-15 10:30:00')],
        ];
    }

    public function testConstructorWithAllParameters(): void
    {
        $generatedAt = new \DateTimeImmutable('2025-01-15 10:30:00');
        $headers = ['员工编号', '姓名', '基本工资', '实发工资'];
        $data = [
            ['employee_number' => 'EMP001', 'name' => '张三', 'base_salary' => 10000, 'net_salary' => 8500],
            ['employee_number' => 'EMP002', 'name' => '李四', 'base_salary' => 12000, 'net_salary' => 10200],
        ];
        $summary = ['total_employees' => 2, 'total_salary' => 18700];
        $metadata = ['report_version' => '1.0', 'format' => 'excel'];

        $report = ReportData::create(
            'salary_report',
            '月度薪资报表',
            $this->period,
            $headers,
            $data,
            $summary,
            $metadata,
            $generatedAt
        );

        $this->assertEquals('salary_report', $report->getReportType());
        $this->assertEquals('月度薪资报表', $report->getTitle());
        $this->assertEquals($this->period, $report->getPeriod());
        $this->assertEquals($headers, $report->getHeaders());
        $this->assertEquals($data, $report->getData());
        $this->assertEquals($summary, $report->getSummary());
        $this->assertEquals($metadata, $report->getMetadata());
        $this->assertEquals($generatedAt, $report->getGeneratedAt());
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $headers = ['员工编号', '姓名'];
        $data = [['employee_number' => 'EMP001', 'name' => '张三']];
        $summary = ['count' => 1];

        $report = ReportData::create(
            'simple_report',
            '简单报表',
            $this->period,
            $headers,
            $data,
            $summary
        );

        $this->assertEquals('simple_report', $report->getReportType());
        $this->assertEquals('简单报表', $report->getTitle());
        $this->assertEquals([], $report->getMetadata());
        $this->assertInstanceOf(\DateTimeImmutable::class, $report->getGeneratedAt());
    }

    public function testGetTotalRows(): void
    {
        $data = [
            ['employee_number' => 'EMP001', 'name' => '张三', 'salary' => 10000],
            ['employee_number' => 'EMP002', 'name' => '李四', 'salary' => 12000],
            ['employee_number' => 'EMP003', 'name' => '王五', 'salary' => 8000],
        ];

        $report = ReportData::create(
            'test_report',
            '测试报表',
            $this->period,
            ['编号', '姓名', '工资'],
            $data,
            []
        );

        $this->assertEquals(3, $report->getTotalRows());
    }

    public function testGetTotalRowsWithEmptyData(): void
    {
        $report = ReportData::create(
            'empty_report',
            '空报表',
            $this->period,
            ['标题'],
            [],
            []
        );

        $this->assertEquals(0, $report->getTotalRows());
    }

    public function testToArray(): void
    {
        $generatedAt = new \DateTimeImmutable('2025-01-15 14:30:00');
        $headers = ['员工编号', '姓名', '工资'];
        $data = [['employee_number' => 'EMP001', 'name' => '张三', 'salary' => 10000]];
        $summary = ['total' => 10000];
        $metadata = ['version' => '2.0'];

        $report = ReportData::create(
            'test_report',
            '测试报表',
            $this->period,
            $headers,
            $data,
            $summary,
            $metadata,
            $generatedAt
        );

        $expected = [
            'report_type' => 'test_report',
            'title' => '测试报表',
            'period' => '2025年1月',
            'headers' => $headers,
            'data' => $data,
            'summary' => $summary,
            'metadata' => $metadata,
            'total_rows' => 1,
            'generated_at' => '2025-01-15 14:30:00',
        ];

        $this->assertEquals($expected, $report->toArray());
    }

    public function testReadOnlyProperties(): void
    {
        $report = ReportData::create(
            'readonly_test',
            '只读测试',
            $this->period,
            ['test'],
            [['key' => 'data']],
            ['summary' => 'test']
        );

        // 验证所有方法都返回正确的类型
        $this->assertIsString($report->getReportType());
        $this->assertIsString($report->getTitle());
        $this->assertInstanceOf(PayrollPeriod::class, $report->getPeriod());
        $this->assertIsArray($report->getHeaders());
        $this->assertIsArray($report->getData());
        $this->assertIsArray($report->getSummary());
        $this->assertIsArray($report->getMetadata());
        $this->assertInstanceOf(\DateTimeImmutable::class, $report->getGeneratedAt());
        $this->assertIsInt($report->getTotalRows());
        $this->assertIsArray($report->toArray());
    }

    public function testComplexReportData(): void
    {
        $complexHeaders = [
            'employee_id', 'name', 'department', 'basic_salary',
            'overtime', 'bonus', 'deductions', 'net_salary',
        ];

        $complexData = [
            ['employee_id' => 'E001', 'name' => '张三', 'department' => '技术部', 'basic_salary' => 15000, 'overtime' => 2000, 'bonus' => 3000, 'deduction' => 1500, 'net_salary' => 18500],
            ['employee_id' => 'E002', 'name' => '李四', 'department' => '销售部', 'basic_salary' => 12000, 'overtime' => 1500, 'bonus' => 5000, 'deduction' => 1200, 'net_salary' => 17300],
            ['employee_id' => 'E003', 'name' => '王五', 'department' => '财务部', 'basic_salary' => 10000, 'overtime' => 800, 'bonus' => 1000, 'deduction' => 900, 'net_salary' => 10900],
        ];

        $complexSummary = [
            'total_employees' => 3,
            'total_basic_salary' => 37000,
            'total_overtime' => 4300,
            'total_bonus' => 9000,
            'total_deductions' => 3600,
            'total_net_salary' => 46700,
            'avg_net_salary' => 15566.67,
        ];

        $report = ReportData::create(
            'department_salary_report',
            '部门薪资汇总报表',
            $this->period,
            $complexHeaders,
            $complexData,
            $complexSummary,
            ['departments' => ['技术部', '销售部', '财务部']]
        );

        $this->assertEquals(3, $report->getTotalRows());
        $this->assertCount(8, $report->getHeaders());
        $this->assertArrayHasKey('total_employees', $report->getSummary());
        $this->assertArrayHasKey('departments', $report->getMetadata());
    }

    public function testGeneratedAtDefaultValue(): void
    {
        $beforeCreation = new \DateTimeImmutable();

        $report = ReportData::create(
            'time_test',
            '时间测试',
            $this->period,
            ['test'],
            [],
            []
        );

        $afterCreation = new \DateTimeImmutable();
        $generatedAt = $report->getGeneratedAt();

        $this->assertGreaterThanOrEqual(
            $beforeCreation->getTimestamp(),
            $generatedAt->getTimestamp()
        );
        $this->assertLessThanOrEqual(
            $afterCreation->getTimestamp(),
            $generatedAt->getTimestamp()
        );
    }

    public function testSettersAndGetters(): void
    {
        $report = new ReportData();

        // 测试 setter 和 getter
        $report->setReportType('test_type');
        $this->assertEquals('test_type', $report->getReportType());

        $report->setTitle('Test Title');
        $this->assertEquals('Test Title', $report->getTitle());

        $report->setPeriod($this->period);
        $this->assertEquals($this->period, $report->getPeriod());

        $headers = ['col1', 'col2'];
        $report->setHeaders($headers);
        $this->assertEquals($headers, $report->getHeaders());

        $data = [['col1' => 'value1', 'col2' => 'value2']];
        $report->setData($data);
        $this->assertEquals($data, $report->getData());

        $summary = ['total' => 1];
        $report->setSummary($summary);
        $this->assertEquals($summary, $report->getSummary());

        $metadata = ['version' => '1.0'];
        $report->setMetadata($metadata);
        $this->assertEquals($metadata, $report->getMetadata());

        $generatedAt = new \DateTimeImmutable('2025-01-01 12:00:00');
        $report->setGeneratedAt($generatedAt);
        $this->assertEquals($generatedAt, $report->getGeneratedAt());
    }

    public function testToString(): void
    {
        $report = ReportData::create(
            'test_report',
            '测试报表',
            $this->period,
            ['test'],
            [],
            []
        );

        $expected = '测试报表 - test_report (2025年1月)';
        $this->assertEquals($expected, (string) $report);
    }
}
