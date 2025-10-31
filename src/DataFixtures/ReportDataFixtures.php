<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\ReportData;

/**
 * 报表数据固定加载器
 */
class ReportDataFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // 创建测试期间
        $period = new PayrollPeriod();
        $period->setYear(2025);
        $period->setMonth(1);
        $manager->persist($period);

        // 月度薪资报表
        $salaryReport = ReportData::create(
            'monthly_salary',
            '2025年1月薪资报表',
            $period,
            ['员工编号', '姓名', '部门', '基本工资', '绩效奖金', '应发工资', '实发工资'],
            [
                ['员工编号' => 'EMP001', '姓名' => '张三', '部门' => '技术部', '基本工资' => 15000, '绩效奖金' => 3000, '应发工资' => 18000, '实发工资' => 15500],
                ['员工编号' => 'EMP002', '姓名' => '李四', '部门' => '销售部', '基本工资' => 12000, '绩效奖金' => 5000, '应发工资' => 17000, '实发工资' => 14200],
                ['员工编号' => 'EMP003', '姓名' => '王五', '部门' => '财务部', '基本工资' => 10000, '绩效奖金' => 1500, '应发工资' => 11500, '实发工资' => 9800],
            ],
            [
                'total_employees' => 3,
                'total_basic_salary' => 37000,
                'total_bonus' => 9500,
                'total_gross_salary' => 46500,
                'total_net_salary' => 39500,
                'avg_net_salary' => 13166.67,
            ],
            ['report_version' => '1.0', 'format' => 'excel', 'department' => 'HR'],
            new \DateTimeImmutable('2025-01-25 14:30:00')
        );
        $manager->persist($salaryReport);

        // 部门汇总报表
        $departmentReport = ReportData::create(
            'department_summary',
            '2025年1月部门薪资汇总',
            $period,
            ['部门', '员工人数', '基本工资总额', '奖金总额', '实发工资总额', '人均实发工资'],
            [
                ['部门' => '技术部', '员工人数' => 1, '基本工资总额' => 15000, '奖金总额' => 3000, '实发工资总额' => 15500, '人均实发工资' => 15500],
                ['部门' => '销售部', '员工人数' => 1, '基本工资总额' => 12000, '奖金总额' => 5000, '实发工资总额' => 14200, '人均实发工资' => 14200],
                ['部门' => '财务部', '员工人数' => 1, '基本工资总额' => 10000, '奖金总额' => 1500, '实发工资总额' => 9800, '人均实发工资' => 9800],
            ],
            [
                'total_departments' => 3,
                'total_employees' => 3,
                'total_basic_salary' => 37000,
                'total_bonus' => 9500,
                'total_net_salary' => 39500,
                'company_avg_salary' => 13166.67,
            ],
            ['report_version' => '1.0', 'format' => 'pdf', 'generated_by' => 'auto'],
            new \DateTimeImmutable('2025-01-26 09:15:00')
        );
        $manager->persist($departmentReport);

        // 税务报表
        $taxReport = ReportData::create(
            'monthly_tax',
            '2025年1月个税报表',
            $period,
            ['员工编号', '姓名', '应税收入', '应纳税所得额', '税率', '速算扣除数', '应纳税额'],
            [
                ['员工编号' => 'EMP001', '姓名' => '张三', '应税收入' => 15500, '应纳税所得额' => 12000, '税率' => 0.10, '速算扣除数' => 210, '应纳税额' => 990],
                ['员工编号' => 'EMP002', '姓名' => '李四', '应税收入' => 14200, '应纳税所得额' => 10700, '税率' => 0.10, '速算扣除数' => 210, '应纳税额' => 860],
                ['员工编号' => 'EMP003', '姓名' => '王五', '应税收入' => 9800, '应纳税所得额' => 6300, '税率' => 0.10, '速算扣除数' => 210, '应纳税额' => 420],
            ],
            [
                'total_taxable_income' => 39500,
                'total_taxable_amount' => 29000,
                'total_tax_amount' => 2270,
                'avg_tax_rate' => 0.0575,
                'total_employees' => 3,
            ],
            ['report_version' => '1.0', 'tax_year' => 2025, 'tax_period' => '2025-01'],
            new \DateTimeImmutable('2025-01-26 16:45:00')
        );
        $manager->persist($taxReport);

        $manager->flush();
    }

    /**
     * 获取分组名称
     *
     * @return array<int, string>
     */
    public static function getGroups(): array
    {
        return ['report-data', 'test-data'];
    }
}