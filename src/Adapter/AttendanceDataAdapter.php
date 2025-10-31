<?php

namespace Tourze\SalaryManageBundle\Adapter;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Exception\DataAccessException;
use Tourze\SalaryManageBundle\Interface\AttendanceDataInterface;
use Tourze\SalaryManageBundle\Interface\ExternalSystemInterface;

class AttendanceDataAdapter implements AttendanceDataInterface
{
    public function __construct(
        private ExternalSystemInterface $externalSystem,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function getAttendanceData(Employee $employee, PayrollPeriod $period): array
    {
        if (!$this->externalSystem->authenticate()) {
            throw new DataAccessException('考勤系统认证失败');
        }

        $params = [
            'employee_id' => $employee->getEmployeeNumber(),
            'start_date' => $period->getStartDate()->format('Y-m-d'),
            'end_date' => $period->getEndDate()->format('Y-m-d'),
        ];

        $rawData = $this->externalSystem->fetchData('/attendance/employee', $params);

        return $this->transformAttendanceData($rawData);
    }

    /**
     * @param array<int, Employee> $employees
     * @return array<string, array<string, mixed>>
     */
    public function getAttendanceSummary(
        array $employees,
        PayrollPeriod $period,
    ): array {
        $summary = [];

        foreach ($employees as $employee) {
            $attendanceData = $this->getAttendanceData($employee, $period);
            $summary[$employee->getEmployeeNumber()] = [
                'working_days' => $this->calculateWorkingDays($employee, $period),
                'overtime_hours' => $this->calculateOvertimeHours($employee, $period),
                'leave_days' => count($this->getLeaveData($employee, $period)),
                'attendance_rate' => $this->calculateAttendanceRate($attendanceData),
            ];
        }

        return $summary;
    }

    public function calculateWorkingDays(Employee $employee, PayrollPeriod $period): float
    {
        $attendanceData = $this->getAttendanceData($employee, $period);

        return array_sum(array_column($attendanceData, 'working_days'));
    }

    public function calculateOvertimeHours(Employee $employee, PayrollPeriod $period): float
    {
        $attendanceData = $this->getAttendanceData($employee, $period);

        return array_sum(array_column($attendanceData, 'overtime_hours'));
    }

    /** @return array<int, array<string, mixed>> */
    public function getLeaveData(Employee $employee, PayrollPeriod $period): array
    {
        $params = [
            'employee_id' => $employee->getEmployeeNumber(),
            'start_date' => $period->getStartDate()->format('Y-m-d'),
            'end_date' => $period->getEndDate()->format('Y-m-d'),
            'type' => 'leave',
        ];

        $rawData = $this->externalSystem->fetchData('/attendance/leave', $params);

        return $this->transformLeaveData($rawData);
    }

    /**
     * @param array<int, array<string, mixed>> $attendanceData
     */
    public function validateAttendanceData(
        array $attendanceData,
    ): bool {
        $requiredFields = ['employee_id', 'date', 'check_in', 'check_out'];

        foreach ($attendanceData as $record) {
            foreach ($requiredFields as $field) {
                if (!isset($record[$field])) {
                    return false;
                }
            }

            $checkIn = is_string($record['check_in']) ? $record['check_in'] : '';
            $checkOut = is_string($record['check_out']) ? $record['check_out'] : '';

            if (!$this->validateTimeFormat($checkIn) || !$this->validateTimeFormat($checkOut)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $rawData
     * @return array<int, array<string, mixed>>
     */
    private function transformAttendanceData(
        array $rawData,
    ): array {
        $transformed = [];

        foreach ($rawData as $record) {
            $transformed[] = [
                'date' => $record['attendance_date'],
                'check_in' => $record['check_in_time'] ?? null,
                'check_out' => $record['check_out_time'] ?? null,
                'working_hours' => $record['total_hours'] ?? 0,
                'overtime_hours' => $record['overtime_hours'] ?? 0,
                'working_days' => $record['working_days'] ?? 1,
                'status' => $this->normalizeStatus(
                    is_string($record['status'] ?? null) ? $record['status'] : 'present'
                ),
            ];
        }

        return $transformed;
    }

    /**
     * @param array<int, array<string, mixed>> $rawData
     * @return array<int, array<string, mixed>>
     */
    private function transformLeaveData(
        array $rawData,
    ): array {
        $transformed = [];

        foreach ($rawData as $record) {
            $transformed[] = [
                'start_date' => $record['leave_start_date'],
                'end_date' => $record['leave_end_date'],
                'days' => $record['leave_days'],
                'type' => $this->normalizeLeaveType(
                    is_string($record['leave_type'] ?? null) ? $record['leave_type'] : 'other'
                ),
                'status' => $record['approval_status'] ?? 'approved',
            ];
        }

        return $transformed;
    }

    private function normalizeStatus(string $status): string
    {
        return match (strtolower($status)) {
            'present', '出勤' => 'present',
            'absent', '缺勤' => 'absent',
            'late', '迟到' => 'late',
            'leave', '请假' => 'leave',
            default => 'unknown',
        };
    }

    private function normalizeLeaveType(string $type): string
    {
        return match (strtolower($type)) {
            'annual', '年假' => 'annual',
            'sick', '病假' => 'sick',
            'personal', '事假' => 'personal',
            'maternity', '产假' => 'maternity',
            default => 'other',
        };
    }

    private function validateTimeFormat(string $time): bool
    {
        $matchResult = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time);
        if (0 === $matchResult || false === $matchResult) {
            return false;
        }

        $parts = explode(':', $time);
        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];

        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return false;
        }

        if (isset($parts[2])) {
            $seconds = (int) $parts[2];
            if ($seconds < 0 || $seconds > 59) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $attendanceData
     */
    private function calculateAttendanceRate(
        array $attendanceData,
    ): float {
        if ([] === $attendanceData) {
            return 0.0;
        }

        $presentDays = count(array_filter($attendanceData, fn ($record) => 'present' === $record['status']));

        return round($presentDays / count($attendanceData) * 100, 2);
    }
}
