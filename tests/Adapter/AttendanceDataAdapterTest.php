<?php

namespace Tourze\SalaryManageBundle\Tests\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\SalaryManageBundle\Adapter\AttendanceDataAdapter;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Interface\ExternalSystemInterface;
use Tourze\SalaryManageBundle\Tests\Helper\MockExternalSystemInterface;

/**
 * @internal
 */
#[CoversClass(AttendanceDataAdapter::class)]
class AttendanceDataAdapterTest extends TestCase
{
    private AttendanceDataAdapter $adapter;

    private MockExternalSystemInterface $externalSystem;

    private Employee $employee;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->externalSystem = new MockExternalSystemInterface();
        $this->adapter = new AttendanceDataAdapter($this->externalSystem);

        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('E001');
        $this->employee->setName('张三');
        $this->employee->setDepartment('技术部');
        $this->employee->setBaseSalary('10000.00');
        $this->employee->setHireDate(new \DateTimeImmutable('2023-01-01'));

        $this->period = new PayrollPeriod();
        $this->period->setYear(2025);
        $this->period->setMonth(1);
    }

    public function testGetAttendanceData(): void
    {
        $mockData = [
            [
                'attendance_date' => '2025-01-15',
                'check_in_time' => '09:00:00',
                'check_out_time' => '18:00:00',
                'total_hours' => 8,
                'overtime_hours' => 1,
                'working_days' => 1,
                'status' => '出勤',
            ],
        ];

        $this->externalSystem->expectCall('authenticate', 1);
        $this->externalSystem->expectCall('fetchData', 1);
        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataResult('/attendance/employee', $mockData);

        $result = $this->adapter->getAttendanceData($this->employee, $this->period);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('2025-01-15', $result[0]['date']);
        $this->assertEquals('09:00:00', $result[0]['check_in']);
        $this->assertEquals('18:00:00', $result[0]['check_out']);
        $this->assertEquals(8, $result[0]['working_hours']);
        $this->assertEquals(1, $result[0]['overtime_hours']);
        $this->assertEquals('present', $result[0]['status']);
        $this->externalSystem->verifyExpectedCalls();
    }

    public function testGetAttendanceDataFailsWhenAuthenticationFails(): void
    {
        $this->externalSystem->expectCall('authenticate', 1);
        $this->externalSystem->setAuthResult(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('考勤系统认证失败');

        try {
            $this->adapter->getAttendanceData($this->employee, $this->period);
        } catch (\RuntimeException $e) {
            $this->externalSystem->verifyExpectedCalls();
            throw $e;
        }
    }

    public function testCalculateWorkingDays(): void
    {
        $mockRawData = [
            [
                'attendance_date' => '2025-01-15',
                'check_in_time' => '09:00:00',
                'check_out_time' => '18:00:00',
                'total_hours' => 8,
                'overtime_hours' => 1,
                'working_days' => 1,
                'status' => '出勤',
            ],
            [
                'attendance_date' => '2025-01-16',
                'check_in_time' => '09:00:00',
                'check_out_time' => '18:00:00',
                'total_hours' => 8,
                'overtime_hours' => 0,
                'working_days' => 1,
                'status' => '出勤',
            ],
            [
                'attendance_date' => '2025-01-17',
                'check_in_time' => '09:00:00',
                'check_out_time' => '13:00:00',
                'total_hours' => 4,
                'overtime_hours' => 0,
                'working_days' => 0.5,
                'status' => '出勤',
            ],
        ];

        $this->externalSystem->expectCall('authenticate', 1);
        $this->externalSystem->expectCall('fetchData', 1);
        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataResult('/attendance/employee', $mockRawData);

        $result = $this->adapter->calculateWorkingDays($this->employee, $this->period);

        $this->assertEquals(2.5, $result);
        $this->externalSystem->verifyExpectedCalls();
    }

    public function testCalculateOvertimeHours(): void
    {
        $mockRawData = [
            [
                'attendance_date' => '2025-01-15',
                'check_in_time' => '09:00:00',
                'check_out_time' => '20:00:00',
                'total_hours' => 10,
                'overtime_hours' => 2,
                'working_days' => 1,
                'status' => '出勤',
            ],
            [
                'attendance_date' => '2025-01-16',
                'check_in_time' => '09:00:00',
                'check_out_time' => '19:30:00',
                'total_hours' => 9.5,
                'overtime_hours' => 1.5,
                'working_days' => 1,
                'status' => '出勤',
            ],
            [
                'attendance_date' => '2025-01-17',
                'check_in_time' => '09:00:00',
                'check_out_time' => '18:00:00',
                'total_hours' => 8,
                'overtime_hours' => 0,
                'working_days' => 1,
                'status' => '出勤',
            ],
        ];

        $this->externalSystem->expectCall('authenticate', 1);
        $this->externalSystem->expectCall('fetchData', 1);
        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataResult('/attendance/employee', $mockRawData);

        $result = $this->adapter->calculateOvertimeHours($this->employee, $this->period);

        $this->assertEquals(3.5, $result);
        $this->externalSystem->verifyExpectedCalls();
    }

    public function testGetLeaveData(): void
    {
        $mockData = [
            [
                'leave_start_date' => '2025-01-10',
                'leave_end_date' => '2025-01-10',
                'leave_days' => 1,
                'leave_type' => '年假',
                'approval_status' => 'approved',
            ],
        ];

        $this->externalSystem->expectCall('fetchData', 1);
        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataResult('/attendance/leave', $mockData);

        $result = $this->adapter->getLeaveData($this->employee, $this->period);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('2025-01-10', $result[0]['start_date']);
        $this->assertEquals('2025-01-10', $result[0]['end_date']);
        $this->assertEquals(1, $result[0]['days']);
        $this->assertEquals('annual', $result[0]['type']);
        $this->assertEquals('approved', $result[0]['status']);
        $this->externalSystem->verifyExpectedCalls();
    }

    public function testValidateAttendanceData(): void
    {
        $validData = [
            [
                'employee_id' => 'E001',
                'date' => '2025-01-15',
                'check_in' => '09:00',
                'check_out' => '18:00',
            ],
        ];

        $this->assertTrue($this->adapter->validateAttendanceData($validData));

        $invalidData = [
            [
                'employee_id' => 'E001',
                'date' => '2025-01-15',
                'check_in' => '09:00',
                // 缺少 check_out
            ],
        ];

        $this->assertFalse($this->adapter->validateAttendanceData($invalidData));
    }

    public function testValidateAttendanceDataWithInvalidTimeFormat(): void
    {
        $invalidTimeData = [
            [
                'employee_id' => 'E001',
                'date' => '2025-01-15',
                'check_in' => '25:00',  // 无效时间
                'check_out' => '18:00',
            ],
        ];

        $this->assertFalse($this->adapter->validateAttendanceData($invalidTimeData));
    }

    public function testGetAttendanceSummary(): void
    {
        $employees = [$this->employee];

        $mockAttendanceRawData = [
            [
                'attendance_date' => '2025-01-15',
                'check_in_time' => '09:00:00',
                'check_out_time' => '18:00:00',
                'total_hours' => 8,
                'overtime_hours' => 2,
                'working_days' => 1,
                'status' => '出勤',
            ],
            [
                'attendance_date' => '2025-01-16',
                'check_in_time' => '09:00:00',
                'check_out_time' => '18:00:00',
                'total_hours' => 8,
                'overtime_hours' => 0,
                'working_days' => 1,
                'status' => '出勤',
            ],
            [
                'attendance_date' => '2025-01-17',
                'check_in_time' => null,
                'check_out_time' => null,
                'total_hours' => 0,
                'overtime_hours' => 0,
                'working_days' => 0,
                'status' => '缺勤',
            ],
        ];

        $mockLeaveRawData = [
            [
                'leave_start_date' => '2025-01-10',
                'leave_end_date' => '2025-01-10',
                'leave_days' => 1,
                'leave_type' => '年假',
                'approval_status' => 'approved',
            ],
        ];

        $this->externalSystem->expectCall('authenticate', 3);
        $this->externalSystem->expectCall('fetchData', 4);
        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataCallback(function ($endpoint, $params) use ($mockAttendanceRawData, $mockLeaveRawData) {
            if ('/attendance/employee' === $endpoint) {
                return $mockAttendanceRawData;
            }
            if ('/attendance/leave' === $endpoint) {
                return $mockLeaveRawData;
            }

            return [];
        });

        $result = $this->adapter->getAttendanceSummary($employees, $this->period);

        $this->assertArrayHasKey('E001', $result);
        $this->assertEquals(2.0, $result['E001']['working_days']);
        $this->assertEquals(2.0, $result['E001']['overtime_hours']);
        $this->assertEquals(1, $result['E001']['leave_days']);
        $this->assertEquals(66.67, $result['E001']['attendance_rate']);
        $this->externalSystem->verifyExpectedCalls();
    }
}
