<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;

class PayrollPeriodFixtures extends Fixture
{
    public const PERIOD_1_REFERENCE = 'period-1';
    public const PERIOD_2_REFERENCE = 'period-2';
    public const PERIOD_3_REFERENCE = 'period-3';

    public function load(ObjectManager $manager): void
    {
        // 创建过去几个月的薪资期间数据
        $currentDate = new \DateTimeImmutable();
        $currentYear = (int) $currentDate->format('Y');
        $currentMonth = (int) $currentDate->format('n');

        // 创建过去6个月的薪资期间
        $periods = [];
        for ($i = 5; $i >= 0; --$i) {
            $targetDate = $currentDate->modify(sprintf('-%d months', $i));
            $year = (int) $targetDate->format('Y');
            $month = (int) $targetDate->format('n');

            $payrollPeriod = new PayrollPeriod();
            $payrollPeriod->setYear($year);
            $payrollPeriod->setMonth($month);

            // 除当前月份外，其他月份标记为已关闭
            if ($i > 0) {
                $payrollPeriod->setIsClosed(true);
            }

            $manager->persist($payrollPeriod);
            $periods[] = $payrollPeriod;

            // 设置引用，供其他 fixtures 使用（保持旧格式兼容性）
            $this->addReference(sprintf('payroll-period-%d-%02d', $year, $month), $payrollPeriod);
        }

        // 添加常量引用（指向最近的3个月）
        if (isset($periods[3])) {
            $this->addReference(self::PERIOD_1_REFERENCE, $periods[3]);
        }
        if (isset($periods[4])) {
            $this->addReference(self::PERIOD_2_REFERENCE, $periods[4]);
        }
        if (isset($periods[5])) {
            $this->addReference(self::PERIOD_3_REFERENCE, $periods[5]);
        }

        // 为了测试需要，额外创建未来的薪资期间
        $futureDate = $currentDate->modify('+1 month');
        $futureYear = (int) $futureDate->format('Y');
        $futureMonth = (int) $futureDate->format('n');

        $futurePayrollPeriod = new PayrollPeriod();
        $futurePayrollPeriod->setYear($futureYear);
        $futurePayrollPeriod->setMonth($futureMonth);
        $manager->persist($futurePayrollPeriod);
        $this->addReference(sprintf('payroll-period-%d-%02d', $futureYear, $futureMonth), $futurePayrollPeriod);

        $manager->flush();
    }
}
