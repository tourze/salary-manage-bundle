<?php

namespace Tourze\SalaryManageBundle\Tests\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\SalaryManageBundle\Adapter\PerformanceDataAdapter;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Exception\DataAccessException;
use Tourze\SalaryManageBundle\Interface\ExternalSystemInterface;
use Tourze\SalaryManageBundle\Tests\Helper\MockExternalSystemInterface;

/**
 * @internal
 */
#[CoversClass(PerformanceDataAdapter::class)]
class PerformanceDataAdapterTest extends TestCase
{
    private PerformanceDataAdapter $adapter;

    private MockExternalSystemInterface $externalSystem;

    private Employee $employee;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->externalSystem = new MockExternalSystemInterface();
        $this->adapter = new PerformanceDataAdapter($this->externalSystem);

        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('EMP001');
        $this->employee->setBaseSalary('10000.00');

        $this->period = new PayrollPeriod();
        $this->period->setYear(2023);
        $this->period->setMonth(12);
    }

    public function testGetPerformanceDataWithSuccessfulAuthentication(): void
    {
        $rawData = [
            'employee_number' => 'EMP001',
            'evaluation_period' => '2023-12',
            'total_score' => 85.5,
            'kpi_score' => 80.0,
            'attitude_score' => 90.0,
            'skill_score' => 88.0,
            'achievements' => ['完成项目A', '优化流程B'],
            'improvement_areas' => ['提升沟通能力'],
            'manager_comments' => '表现优秀',
            'status' => 'approved',
        ];

        $this->externalSystem->expectCall('authenticate', 1);
        $this->externalSystem->expectCall('fetchData', 1);
        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataResult('/performance/employee', [$rawData]); // 包装在数组中

        $result = $this->adapter->getPerformanceData($this->employee, $this->period);

        $expected = [
            'employee_id' => 'EMP001',
            'period' => '2023-12',
            'overall_score' => 85.5,
            'kpi_score' => 80.0,
            'attitude_score' => 90.0,
            'skill_score' => 88.0,
            'achievements' => ['完成项目A', '优化流程B'],
            'improvements' => ['提升沟通能力'],
            'comments' => '表现优秀',
            'status' => 'approved',
        ];

        $this->assertEquals($expected, $result);
        $this->externalSystem->verifyExpectedCalls();
    }

    public function testGetPerformanceDataThrowsExceptionWhenAuthenticationFails(): void
    {
        $this->externalSystem->expectCall('authenticate', 1);
        $this->externalSystem->setAuthResult(false);

        $this->expectException(DataAccessException::class);
        $this->expectExceptionMessage('绩效系统认证失败');

        try {
            $this->adapter->getPerformanceData($this->employee, $this->period);
        } catch (DataAccessException $e) {
            $this->externalSystem->verifyExpectedCalls();
            throw $e;
        }
    }

    public function testGetPerformanceScore(): void
    {
        $rawData = [
            'employee_number' => 'EMP001',
            'evaluation_period' => '2023-12',
            'total_score' => 92.5,
        ];

        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataResult('/performance/employee', [$rawData]);

        $score = $this->adapter->getPerformanceScore($this->employee, $this->period);

        $this->assertEquals(92.5, $score);
    }

    public function testGetPerformanceScoreReturnsZeroWhenScoreMissing(): void
    {
        $rawData = [
            'employee_number' => 'EMP001',
            'evaluation_period' => '2023-12',
            'total_score' => null,
        ];

        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataResult('/performance/employee', [$rawData]);

        $score = $this->adapter->getPerformanceScore($this->employee, $this->period);

        $this->assertEquals(0.0, $score);
    }

    public function testGetPerformanceBonus(): void
    {
        $rawData = [
            'employee_number' => 'EMP001',
            'evaluation_period' => '2023-12',
            'total_score' => 95.0,
        ];

        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataResult('/performance/employee', [$rawData]);

        $bonus = $this->adapter->getPerformanceBonus($this->employee, $this->period);

        // 95分对应30%奖金率，基础工资10000
        $this->assertEquals(3000.0, $bonus);
    }

    public function testGetKpiResults(): void
    {
        $rawKpiData = [
            [
                'kpi_name' => '销售完成率',
                'target_value' => 100,
                'actual_value' => 120,
                'score' => 95.0,
                'weight' => 0.4,
                'category' => 'sales',
            ],
            [
                'kpi_name' => '客户满意度',
                'target_value' => 90,
                'actual_value' => 88,
                'score' => 80.0,
                'weight' => 0.3,
            ],
        ];

        $this->externalSystem->expectCall('fetchData', 1);
        $this->externalSystem->setFetchDataResult('/performance/kpi', $rawKpiData);

        $result = $this->adapter->getKpiResults($this->employee, $this->period);

        $expected = [
            [
                'kpi_name' => '销售完成率',
                'target' => 100,
                'actual' => 120,
                'score' => 95.0,
                'weight' => 0.4,
                'category' => 'sales',
            ],
            [
                'kpi_name' => '客户满意度',
                'target' => 90,
                'actual' => 88,
                'score' => 80.0,
                'weight' => 0.3,
                'category' => 'general',
            ],
        ];

        $this->assertEquals($expected, $result);
        $this->externalSystem->verifyExpectedCalls();
    }

    #[TestWith([95.0, 1.2])]
    #[TestWith([90.0, 1.2])]
    #[TestWith([85.0, 1.1])]
    #[TestWith([70.0, 1.0])]
    #[TestWith([65.0, 0.9])]
    #[TestWith([50.0, 0.8])]
    public function testCalculatePerformanceMultiplier(float $score, float $expectedMultiplier): void
    {
        $rawData = [
            'employee_number' => 'EMP001',
            'evaluation_period' => '2023-12',
            'total_score' => $score,
        ];

        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataResult('/performance/employee', [$rawData]);

        $multiplier = $this->adapter->calculatePerformanceMultiplier($this->employee, $this->period);

        $this->assertEquals($expectedMultiplier, $multiplier);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([['employee_id' => 'EMP001', 'period' => '2023-12', 'overall_score' => 85.5]])]
    #[TestWith([['employee_id' => 'EMP001', 'period' => '2023-12', 'overall_score' => 0]])]
    #[TestWith([['employee_id' => 'EMP001', 'period' => '2023-12', 'overall_score' => 100]])]
    public function testValidatePerformanceDataWithValidData(array $data): void
    {
        /** @var array<string, mixed> $data */
        $this->assertTrue($this->adapter->validatePerformanceData($data));
    }

    /** @param array<string, mixed> $data */
    #[TestWith([['period' => '2023-12', 'overall_score' => 85.5]])]
    #[TestWith([['employee_id' => 'EMP001', 'overall_score' => 85.5]])]
    #[TestWith([['employee_id' => 'EMP001', 'period' => '2023-12']])]
    #[TestWith([['employee_id' => 'EMP001', 'period' => '2023-12', 'overall_score' => -10]])]
    #[TestWith([['employee_id' => 'EMP001', 'period' => '2023-12', 'overall_score' => 150]])]
    #[TestWith([['employee_id' => 'EMP001', 'period' => '2023-12', 'overall_score' => 'excellent']])]
    public function testValidatePerformanceDataWithInvalidData(array $data): void
    {
        /** @var array<string, mixed> $data */
        $this->assertFalse($this->adapter->validatePerformanceData($data));
    }

    public function testTransformPerformanceDataWithMissingFields(): void
    {
        $rawData = [
            'employee_number' => 'EMP001',
            'evaluation_period' => '2023-12',
            'total_score' => 0,
            // 其他字段缺失
        ];

        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataResult('/performance/employee', [$rawData]);

        $result = $this->adapter->getPerformanceData($this->employee, $this->period);

        $expected = [
            'employee_id' => 'EMP001',
            'period' => '2023-12',
            'overall_score' => 0.0,
            'kpi_score' => 0.0,
            'attitude_score' => 0.0,
            'skill_score' => 0.0,
            'achievements' => [],
            'improvements' => [],
            'comments' => '',
            'status' => 'pending',
        ];

        $this->assertEquals($expected, $result);
    }

    #[TestWith(['approved', 'approved'])]
    #[TestWith(['已审批', 'approved'])]
    #[TestWith(['pending', 'pending'])]
    #[TestWith(['待审批', 'pending'])]
    #[TestWith(['draft', 'draft'])]
    #[TestWith(['草稿', 'draft'])]
    #[TestWith(['rejected', 'rejected'])]
    #[TestWith(['已拒绝', 'rejected'])]
    #[TestWith(['invalid', 'unknown'])]
    #[TestWith(['APPROVED', 'approved'])]
    public function testStatusNormalization(string $inputStatus, string $expectedStatus): void
    {
        $rawData = [
            'employee_number' => 'EMP001',
            'evaluation_period' => '2023-12',
            'total_score' => 85.0,
            'status' => $inputStatus,
        ];

        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataResult('/performance/employee', [$rawData]);

        $result = $this->adapter->getPerformanceData($this->employee, $this->period);

        $this->assertEquals($expectedStatus, $result['status']);
    }

    #[TestWith([95.0, 10000.0, 3000.0])]
    #[TestWith([90.0, 10000.0, 2000.0])]
    #[TestWith([85.0, 10000.0, 1500.0])]
    #[TestWith([80.0, 10000.0, 1000.0])]
    #[TestWith([75.0, 10000.0, 500.0])]
    #[TestWith([70.0, 10000.0, 0.0])]
    #[TestWith([95.0, 15000.0, 4500.0])]
    #[TestWith([95.0, 10001.0, 3000.3])]
    public function testBonusCalculation(float $score, float $baseSalary, float $expectedBonus): void
    {
        $rawData = [
            'employee_number' => 'EMP002',
            'evaluation_period' => '2023-12',
            'total_score' => $score,
        ];

        $employee = new Employee();
        $employee->setEmployeeNumber('EMP002');
        $employee->setBaseSalary((string) $baseSalary);

        $this->externalSystem->setAuthResult(true);
        $this->externalSystem->setFetchDataResult('/performance/employee', [$rawData]);

        $bonus = $this->adapter->getPerformanceBonus($employee, $this->period);

        $this->assertEquals($expectedBonus, $bonus);
    }

    public function testTransformKpiDataWithEmptyArray(): void
    {
        $this->externalSystem->setFetchDataResult('/performance/kpi', []);

        $result = $this->adapter->getKpiResults($this->employee, $this->period);

        $this->assertEquals([], $result);
    }
}
