<?php

namespace Tourze\SalaryManageBundle\Interface;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;

interface AttendanceDataInterface
{
    /** @return array<int, array<string, mixed>> */
    public function getAttendanceData(Employee $employee, PayrollPeriod $period): array;

    /**
     * @param array<int, Employee> $employees
     * @return array<string, array<string, mixed>>
     */
    public function getAttendanceSummary(
        array $employees,
        PayrollPeriod $period,
    ): array;

    public function calculateWorkingDays(Employee $employee, PayrollPeriod $period): float;

    public function calculateOvertimeHours(Employee $employee, PayrollPeriod $period): float;

    /** @return array<int, array<string, mixed>> */
    public function getLeaveData(Employee $employee, PayrollPeriod $period): array;

    /**
     * @param array<int, array<string, mixed>> $attendanceData
     */
    public function validateAttendanceData(
        array $attendanceData,
    ): bool;
}
