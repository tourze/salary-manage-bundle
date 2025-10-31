<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\ReportData;
use Tourze\SalaryManageBundle\Repository\ReportDataRepository;

/**
 * @internal
 */
#[CoversClass(ReportDataRepository::class)]
#[RunTestsInSeparateProcesses]
class ReportDataRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepository(): ReportDataRepository
    {
        return self::getService(ReportDataRepository::class);
    }

    protected function createNewEntity(): object
    {
        $period = new PayrollPeriod();
        $period->setYear(2024);
        $period->setMonth(1);

        // 先持久化PayrollPeriod以解决级联问题
        self::getEntityManager()->persist($period);

        $reportData = new ReportData();
        $reportData->setReportType('test_report_' . time());
        $reportData->setTitle('测试报表标题_' . time());
        $reportData->setPeriod($period);
        $reportData->setHeaders(['姓名', '工资', '部门']);
        $reportData->setData([['name' => '张三', 'salary' => 5000, 'department' => '技术部']]);
        $reportData->setSummary(['total' => 1, 'amount' => 5000]);

        return $reportData;
    }

    protected function onSetUp(): void
    {
        // Repository测试不需要特殊的setUp逻辑
    }

    public function testFindByReportType(): void
    {
        $reportType = 'salary_summary';
        $result = $this->getRepository()->findByReportType($reportType);
        $this->assertIsArray($result);
    }

    public function testFindByPeriod(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2024);
        $period->setMonth(1);

        // 必须先持久化 period 以便有 ID
        self::getEntityManager()->persist($period);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findByPeriod($period);
        $this->assertIsArray($result);
    }

    public function testFindOneByReportTypeAndPeriod(): void
    {
        $reportType = 'salary_summary';
        $period = new PayrollPeriod();
        $period->setYear(2024);
        $period->setMonth(1);

        // 必须先持久化 period 以便有 ID
        self::getEntityManager()->persist($period);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findOneByReportTypeAndPeriod($reportType, $period);
        $this->assertNull($result);
    }

    public function testFindLatest(): void
    {
        $result = $this->getRepository()->findLatest();
        $this->assertInstanceOf(ReportData::class, $result);
        $this->assertSame('monthly_tax', $result->getReportType());
        $this->assertSame('2025年1月个税报表', $result->getTitle());
    }

    public function testFindLatestByReportType(): void
    {
        $reportType = 'salary_summary';
        $result = $this->getRepository()->findLatestByReportType($reportType);
        $this->assertNull($result);
    }

    public function testDeleteByPeriod(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2024);
        $period->setMonth(1);

        // 必须先持久化 period 以便有 ID
        self::getEntityManager()->persist($period);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->deleteByPeriod($period);
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testCountByPeriod(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2024);
        $period->setMonth(1);

        // 必须先持久化 period 以便有 ID
        self::getEntityManager()->persist($period);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->countByPeriod($period);
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testFindGeneratedAfter(): void
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->getRepository()->findGeneratedAfter($date);
        $this->assertIsArray($result);
    }

    public function testSaveAndRemove(): void
    {
        $period = new PayrollPeriod();
        $period->setYear(2024);
        $period->setMonth(1);

        // 先持久化期间
        self::getEntityManager()->persist($period);
        self::getEntityManager()->flush();

        $reportData = new ReportData();
        $reportData->setReportType('test_report');
        $reportData->setTitle('测试报表');
        $reportData->setPeriod($period);
        $reportData->setHeaders(['test']);
        $reportData->setData([['test' => 'value']]);
        $reportData->setSummary(['test' => 'data']);

        $repository = $this->getRepository();
        $repository->save($reportData, true);
        $this->assertNotNull($reportData->getId());

        $savedId = $reportData->getId();
        $repository->remove($reportData, true);
        $this->assertNull($repository->find($savedId));
    }

}