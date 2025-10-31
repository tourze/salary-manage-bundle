<?php

namespace Tourze\SalaryManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Repository\PayrollPeriodRepository;

/**
 * PayrollPeriod Repository 测试
 * @internal
 */
#[CoversClass(PayrollPeriodRepository::class)]
#[RunTestsInSeparateProcesses]
class PayrollPeriodRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $period = new PayrollPeriod();
        $period->setYear((int) date('Y'));
        $period->setMonth((int) date('n'));

        return $period;
    }

    protected function getRepository(): PayrollPeriodRepository
    {
        $repository = self::getEntityManager()->getRepository(PayrollPeriod::class);
        self::assertInstanceOf(PayrollPeriodRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // 子类可以实现额外的设置逻辑
    }

    public function testFindCurrent(): void
    {
        $repository = $this->getRepository();

        // 创建当前月份的期间
        $period = new PayrollPeriod();
        $now = new \DateTimeImmutable();
        $period->setYear((int) $now->format('Y'));
        $period->setMonth((int) $now->format('n'));

        $em = self::getEntityManager();
        $em->persist($period);
        $em->flush();

        // 查找当前期间
        $currentPeriod = $repository->findCurrent();

        $this->assertNotNull($currentPeriod);
        $this->assertEquals((int) $now->format('Y'), $currentPeriod->getYear());
        $this->assertEquals((int) $now->format('n'), $currentPeriod->getMonth());
    }

    public function testFindByYearMonth(): void
    {
        $repository = $this->getRepository();

        // 创建特定年月的期间
        $period = new PayrollPeriod();
        $period->setYear(2024);
        $period->setMonth(6);

        $em = self::getEntityManager();
        $em->persist($period);
        $em->flush();

        // 按年月查找
        $foundPeriod = $repository->findByYearMonth(2024, 6);

        $this->assertNotNull($foundPeriod);
        $this->assertEquals(2024, $foundPeriod->getYear());
        $this->assertEquals(6, $foundPeriod->getMonth());
    }

    public function testFindOpen(): void
    {
        $repository = $this->getRepository();

        // 创建开放期间
        $openPeriod = new PayrollPeriod();
        $openPeriod->setYear(2024);
        $openPeriod->setMonth(7);
        $openPeriod->setIsClosed(false);

        $em = self::getEntityManager();
        $em->persist($openPeriod);
        $em->flush();

        // 查找所有开放的期间
        $openPeriods = $repository->findOpen();

        $this->assertIsArray($openPeriods);
        $this->assertGreaterThanOrEqual(1, count($openPeriods));

        // 验证都是未关闭的
        foreach ($openPeriods as $period) {
            $this->assertInstanceOf(PayrollPeriod::class, $period);
            $this->assertFalse($period->isClosed());
        }
    }

    public function testFindClosed(): void
    {
        $repository = $this->getRepository();

        // 创建关闭期间
        $closedPeriod = new PayrollPeriod();
        $closedPeriod->setYear(2024);
        $closedPeriod->setMonth(5);
        $closedPeriod->setIsClosed(true);

        $em = self::getEntityManager();
        $em->persist($closedPeriod);
        $em->flush();

        // 查找所有关闭的期间
        $closedPeriods = $repository->findClosed();

        $this->assertIsArray($closedPeriods);
        $this->assertGreaterThanOrEqual(1, count($closedPeriods));

        // 验证都是已关闭的
        foreach ($closedPeriods as $period) {
            $this->assertInstanceOf(PayrollPeriod::class, $period);
            $this->assertTrue($period->isClosed());
        }
    }

    public function testFindByYear(): void
    {
        $repository = $this->getRepository();

        // 创建同一年的多个期间
        $em = self::getEntityManager();
        for ($month = 1; $month <= 3; ++$month) {
            $period = new PayrollPeriod();
            $period->setYear(2023);
            $period->setMonth($month);
            $em->persist($period);
        }
        $em->flush();

        // 按年查找
        $periods2023 = $repository->findByYear(2023);

        $this->assertIsArray($periods2023);
        $this->assertGreaterThanOrEqual(3, count($periods2023));

        // 验证都是2023年的，并且按月份升序排列
        $lastMonth = 0;
        foreach ($periods2023 as $period) {
            $this->assertEquals(2023, $period->getYear());
            $this->assertGreaterThanOrEqual($lastMonth, $period->getMonth());
            $lastMonth = $period->getMonth();
        }
    }
}