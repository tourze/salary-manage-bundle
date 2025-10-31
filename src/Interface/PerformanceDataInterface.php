<?php

namespace Tourze\SalaryManageBundle\Interface;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;

interface PerformanceDataInterface
{
    /** @return array<string, mixed> */
    public function getPerformanceData(Employee $employee, PayrollPeriod $period): array;

    public function getPerformanceScore(Employee $employee, PayrollPeriod $period): float;

    public function getPerformanceBonus(Employee $employee, PayrollPeriod $period): float;

    /** @return array<int, array<string, mixed>> */
    public function getKpiResults(Employee $employee, PayrollPeriod $period): array;

    public function calculatePerformanceMultiplier(Employee $employee, PayrollPeriod $period): float;

    /**
     * @param array<string, mixed> $performanceData
     */
    public function validatePerformanceData(
        array $performanceData,
    ): bool;
}
