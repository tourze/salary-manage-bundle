<?php

namespace Tourze\SalaryManageBundle\Service\Rules;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryItem;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;
use Tourze\SalaryManageBundle\Service\CalculationRuleInterface;

/**
 * 加班费计算规则
 * 根据加班时间计算加班费
 */
class OvertimeRule implements CalculationRuleInterface
{
    public function getType(): string
    {
        return SalaryItemType::Overtime->value;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function calculate(Employee $employee, PayrollPeriod $period, array $context = []): SalaryItem
    {
        $overtimeHoursValue = $context['overtime_hours'] ?? 0;
        $overtimeHours = is_numeric($overtimeHoursValue) ? (float) $overtimeHoursValue : 0.0;

        $hourlyRate = $this->calculateHourlyRate($employee);

        $overtimeMultiplierValue = $context['overtime_multiplier'] ?? 1.5;
        $overtimeMultiplier = is_numeric($overtimeMultiplierValue) ? (float) $overtimeMultiplierValue : 1.5;

        $overtimeAmount = $overtimeHours * $hourlyRate * $overtimeMultiplier;

        $item = new SalaryItem();
        $item->setType(SalaryItemType::Overtime);
        $item->setAmount($overtimeAmount);
        $item->setDescription(sprintf('加班费 (%.1f小时 × %.1f倍)', $overtimeHours, $overtimeMultiplier));
        $item->setMetadata([
            'overtime_hours' => $overtimeHours,
            'hourly_rate' => $hourlyRate,
            'overtime_multiplier' => $overtimeMultiplier,
            'period' => $period->getKey(),
        ]);

        return $item;
    }

    public function isApplicable(Employee $employee): bool
    {
        return (float) $employee->getBaseSalary() > 0;
    }

    public function getOrder(): int
    {
        return 20; // 在基本工资之后计算
    }

    private function calculateHourlyRate(Employee $employee): float
    {
        // 假设月工作时间为21.75天 * 8小时 = 174小时
        $monthlyWorkingHours = 174;

        return (float) $employee->getBaseSalary() / $monthlyWorkingHours;
    }
}
