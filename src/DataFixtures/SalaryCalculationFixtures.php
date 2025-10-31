<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Entity\SalaryItem;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;

/**
 * 薪资计算测试数据
 */
final class SalaryCalculationFixtures extends Fixture implements DependentFixtureInterface
{
    public const SALARY_CALCULATION_001_REFERENCE = 'salary-calculation-001';
    public const SALARY_CALCULATION_002_REFERENCE = 'salary-calculation-002';
    public const SALARY_CALCULATION_003_REFERENCE = 'salary-calculation-003';

    public function load(ObjectManager $manager): void
    {
        /** @var Employee $employee1 */
        $employee1 = $this->getReference(EmployeeFixtures::EMPLOYEE_1_REFERENCE, Employee::class);
        /** @var Employee $employee2 */
        $employee2 = $this->getReference(EmployeeFixtures::EMPLOYEE_2_REFERENCE, Employee::class);

        // 获取当前日期前几个月的期间
        $currentDate = new \DateTimeImmutable();
        $period1Date = $currentDate->modify('-2 months');
        $period2Date = $currentDate->modify('-1 month');

        /** @var PayrollPeriod $period1 */
        $period1 = $this->getReference(sprintf('payroll-period-%d-%02d', (int) $period1Date->format('Y'), (int) $period1Date->format('n')), PayrollPeriod::class);
        /** @var PayrollPeriod $period2 */
        $period2 = $this->getReference(sprintf('payroll-period-%d-%02d', (int) $period2Date->format('Y'), (int) $period2Date->format('n')), PayrollPeriod::class);

        // 为员工1创建2024年1月的薪资计算
        $calculation1 = new SalaryCalculation();
        $calculation1->setEmployee($employee1);
        $calculation1->setPeriod($period1);

        // 添加基本工资项目
        $baseSalaryItem = new SalaryItem();
        $baseSalaryItem->setType(SalaryItemType::BasicSalary);
        $baseSalaryItem->setAmount(8000.00);
        $baseSalaryItem->setDescription('基本工资');
        $calculation1->addItem($baseSalaryItem);

        // 添加绩效奖金
        $bonusItem = new SalaryItem();
        $bonusItem->setType(SalaryItemType::PerformanceBonus);
        $bonusItem->setAmount(2000.00);
        $bonusItem->setDescription('绩效奖金');
        $calculation1->addItem($bonusItem);

        // 添加社保扣款
        $socialInsuranceItem = new SalaryItem();
        $socialInsuranceItem->setType(SalaryItemType::SocialInsurance);
        $socialInsuranceItem->setAmount(-800.00);
        $socialInsuranceItem->setDescription('社保扣款');
        $calculation1->addItem($socialInsuranceItem);

        // 添加个税扣款
        $taxItem = new SalaryItem();
        $taxItem->setType(SalaryItemType::IncomeTax);
        $taxItem->setAmount(-300.00);
        $taxItem->setDescription('个人所得税');
        $calculation1->addItem($taxItem);

        $manager->persist($calculation1);

        // 为员工2创建2024年1月的薪资计算
        $calculation2 = new SalaryCalculation();
        $calculation2->setEmployee($employee2);
        $calculation2->setPeriod($period1);

        $baseSalaryItem2 = new SalaryItem();
        $baseSalaryItem2->setType(SalaryItemType::BasicSalary);
        $baseSalaryItem2->setAmount(9500.00);
        $baseSalaryItem2->setDescription('基本工资');
        $calculation2->addItem($baseSalaryItem2);

        $bonusItem2 = new SalaryItem();
        $bonusItem2->setType(SalaryItemType::PerformanceBonus);
        $bonusItem2->setAmount(1500.00);
        $bonusItem2->setDescription('绩效奖金');
        $calculation2->addItem($bonusItem2);

        $socialInsuranceItem2 = new SalaryItem();
        $socialInsuranceItem2->setType(SalaryItemType::SocialInsurance);
        $socialInsuranceItem2->setAmount(-950.00);
        $socialInsuranceItem2->setDescription('社保扣款');
        $calculation2->addItem($socialInsuranceItem2);

        $taxItem2 = new SalaryItem();
        $taxItem2->setType(SalaryItemType::IncomeTax);
        $taxItem2->setAmount(-450.00);
        $taxItem2->setDescription('个人所得税');
        $calculation2->addItem($taxItem2);

        $manager->persist($calculation2);

        // 为员工1创建2024年2月的薪资计算
        $calculation3 = new SalaryCalculation();
        $calculation3->setEmployee($employee1);
        $calculation3->setPeriod($period2);

        $baseSalaryItem3 = new SalaryItem();
        $baseSalaryItem3->setType(SalaryItemType::BasicSalary);
        $baseSalaryItem3->setAmount(8000.00);
        $baseSalaryItem3->setDescription('基本工资');
        $calculation3->addItem($baseSalaryItem3);

        $overtimeItem = new SalaryItem();
        $overtimeItem->setType(SalaryItemType::Overtime);
        $overtimeItem->setAmount(500.00);
        $overtimeItem->setDescription('加班费');
        $calculation3->addItem($overtimeItem);

        $socialInsuranceItem3 = new SalaryItem();
        $socialInsuranceItem3->setType(SalaryItemType::SocialInsurance);
        $socialInsuranceItem3->setAmount(-800.00);
        $socialInsuranceItem3->setDescription('社保扣款');
        $calculation3->addItem($socialInsuranceItem3);

        $taxItem3 = new SalaryItem();
        $taxItem3->setType(SalaryItemType::IncomeTax);
        $taxItem3->setAmount(-250.00);
        $taxItem3->setDescription('个人所得税');
        $calculation3->addItem($taxItem3);

        $manager->persist($calculation3);

        $manager->flush();

        // 设置引用，供其他fixtures使用
        $this->addReference(self::SALARY_CALCULATION_001_REFERENCE, $calculation1);
        $this->addReference(self::SALARY_CALCULATION_002_REFERENCE, $calculation2);
        $this->addReference(self::SALARY_CALCULATION_003_REFERENCE, $calculation3);
    }

    /**
     * @return array<class-string<FixtureInterface>>
     */
    public function getDependencies(): array
    {
        return [
            EmployeeFixtures::class,
            PayrollPeriodFixtures::class,
        ];
    }
}
