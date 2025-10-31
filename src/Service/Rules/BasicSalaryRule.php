<?php

namespace Tourze\SalaryManageBundle\Service\Rules;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Entity\SalaryItem;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;
use Tourze\SalaryManageBundle\Service\CalculationRuleInterface;

/**
 * 基本薪资计算规则
 * 计算员工的基本工资
 */
class BasicSalaryRule implements CalculationRuleInterface
{
    public function getType(): string
    {
        return SalaryItemType::BasicSalary->value;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function calculate(Employee $employee, PayrollPeriod $period, array $context = []): SalaryItem
    {
        // 支持通过上下文覆盖基本薪资
        $baseSalaryOverride = $context['base_salary_override'] ?? null;
        $baseSalary = is_numeric($baseSalaryOverride) ? (float) $baseSalaryOverride : (float) $employee->getBaseSalary();

        $item = new SalaryItem();
        $item->setType(SalaryItemType::BasicSalary);
        $item->setAmount($baseSalary);
        $item->setDescription('基本工资');
        $item->setMetadata([
            'employee_id' => $employee->getId(),
            'employee_number' => $employee->getEmployeeNumber(),
            'period' => $period->getKey(),
        ]);

        return $item;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function isApplicable(Employee $employee, ?SalaryCalculation $calculation = null, array $context = []): bool
    {
        return (float) $employee->getBaseSalary() > 0;
    }

    public function getOrder(): int
    {
        return 10; // 基本工资优先计算
    }

    public function getName(): string
    {
        return '基本薪资规则';
    }

    public function getDescription(): string
    {
        return '计算员工的基本工资';
    }

    /**
     * @param array<string, mixed> $context
     */
    public function execute(Employee $employee, SalaryCalculation $calculation, array $context = []): void
    {
        // 处理基本薪资覆盖和按天计算
        $baseSalaryOverride = $context['base_salary_override'] ?? null;
        $baseSalary = is_numeric($baseSalaryOverride) ? (float) $baseSalaryOverride : (float) $employee->getBaseSalary();

        // 处理按天计算（适用于新入职员工）
        if (isset($context['worked_days'])) {
            $workedDaysValue = $context['worked_days'];
            $workedDays = is_numeric($workedDaysValue) ? (int) $workedDaysValue : 0;
            $totalDaysInMonth = $calculation->getPeriod()->getDaysInMonth();
            $baseSalary = $baseSalary * ($workedDays / $totalDaysInMonth);
        }

        $item = new SalaryItem();
        $item->setType(SalaryItemType::BasicSalary);
        $item->setAmount($baseSalary);
        $item->setDescription('基本工资');
        $item->setMetadata([
            'employee_id' => $employee->getId(),
            'employee_number' => $employee->getEmployeeNumber(),
            'period' => $calculation->getPeriod()->getKey(),
        ]);

        $calculation->addItem($item);
    }
}
