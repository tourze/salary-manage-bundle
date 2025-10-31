<?php

namespace Tourze\SalaryManageBundle\Adapter;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Exception\DataAccessException;
use Tourze\SalaryManageBundle\Interface\ExternalSystemInterface;
use Tourze\SalaryManageBundle\Interface\PerformanceDataInterface;

class PerformanceDataAdapter implements PerformanceDataInterface
{
    public function __construct(
        private ExternalSystemInterface $externalSystem,
    ) {
    }

    /** @return array<string, mixed> */
    public function getPerformanceData(Employee $employee, PayrollPeriod $period): array
    {
        if (!$this->externalSystem->authenticate()) {
            throw new DataAccessException('绩效系统认证失败');
        }

        $params = [
            'employee_id' => $employee->getEmployeeNumber(),
            'period' => $period->getKey(),
        ];

        $rawDataList = $this->externalSystem->fetchData('/performance/employee', $params);

        // 从返回的记录列表中获取第一条记录
        if (0 === count($rawDataList)) {
            throw new DataAccessException('未找到员工绩效数据');
        }

        $rawData = $rawDataList[0];
        $this->validateRawData($rawData);

        return $this->transformPerformanceData($rawData);
    }

    public function getPerformanceScore(Employee $employee, PayrollPeriod $period): float
    {
        $performanceData = $this->getPerformanceData($employee, $period);

        $score = $performanceData['overall_score'] ?? 0.0;

        return is_numeric($score) ? (float) $score : 0.0;
    }

    public function getPerformanceBonus(Employee $employee, PayrollPeriod $period): float
    {
        $performanceData = $this->getPerformanceData($employee, $period);
        $scoreValue = $performanceData['overall_score'] ?? 0.0;
        $score = is_numeric($scoreValue) ? (float) $scoreValue : 0.0;
        $baseSalary = (float) $employee->getBaseSalary();

        return $this->calculateBonus($score, $baseSalary);
    }

    /** @return array<int, array<string, mixed>> */
    public function getKpiResults(Employee $employee, PayrollPeriod $period): array
    {
        $params = [
            'employee_id' => $employee->getEmployeeNumber(),
            'period' => $period->getKey(),
        ];

        $rawKpiDataList = $this->externalSystem->fetchData('/performance/kpi', $params);

        return $this->transformKpiData($rawKpiDataList);
    }

    public function calculatePerformanceMultiplier(Employee $employee, PayrollPeriod $period): float
    {
        $score = $this->getPerformanceScore($employee, $period);

        return match (true) {
            $score >= 90 => 1.2,    // 优秀
            $score >= 80 => 1.1,    // 良好
            $score >= 70 => 1.0,    // 合格
            $score >= 60 => 0.9,    // 待改进
            default => 0.8,         // 不合格
        };
    }

    /**
     * @param array<string, mixed> $performanceData
     */
    public function validatePerformanceData(
        array $performanceData,
    ): bool {
        $requiredFields = ['employee_id', 'period', 'overall_score'];

        foreach ($requiredFields as $field) {
            if (!isset($performanceData[$field])) {
                return false;
            }
        }

        $score = $performanceData['overall_score'];
        if (!is_numeric($score) || $score < 0 || $score > 100) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $rawData
     * @return array<string, mixed>
     */
    private function transformPerformanceData(
        array $rawData,
    ): array {
        return [
            'employee_id' => $rawData['employee_number'] ?? '',
            'period' => $rawData['evaluation_period'] ?? '',
            'overall_score' => $this->extractFloatValue($rawData, 'total_score'),
            'kpi_score' => $this->extractFloatValue($rawData, 'kpi_score'),
            'attitude_score' => $this->extractFloatValue($rawData, 'attitude_score'),
            'skill_score' => $this->extractFloatValue($rawData, 'skill_score'),
            'achievements' => $rawData['achievements'] ?? [],
            'improvements' => $rawData['improvement_areas'] ?? [],
            'comments' => $rawData['manager_comments'] ?? '',
            'status' => $this->normalizeStatus(
                is_string($rawData['status'] ?? null) ? $rawData['status'] : 'pending'
            ),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rawData
     * @return array<int, array<string, mixed>>
     */
    private function transformKpiData(
        array $rawData,
    ): array {
        $kpiResults = [];

        foreach ($rawData as $item) {
            $kpiResults[] = [
                'kpi_name' => $item['kpi_name'],
                'target' => $item['target_value'],
                'actual' => $item['actual_value'],
                'score' => $this->extractFloatValue($item, 'score'),
                'weight' => $this->extractFloatValue($item, 'weight'),
                'category' => $item['category'] ?? 'general',
            ];
        }

        return $kpiResults;
    }

    private function calculateBonus(float $score, float $baseSalary): float
    {
        $bonusRate = match (true) {
            $score >= 95 => 0.3,    // 30% 奖金
            $score >= 90 => 0.2,    // 20% 奖金
            $score >= 85 => 0.15,   // 15% 奖金
            $score >= 80 => 0.1,    // 10% 奖金
            $score >= 75 => 0.05,   // 5% 奖金
            default => 0.0,         // 无奖金
        };

        return round($baseSalary * $bonusRate, 2);
    }

    /**
     * @param array<string, mixed> $rawData
     */
    private function validateRawData(array $rawData): void
    {
        if (0 === count($rawData)) {
            throw new DataAccessException('外部系统返回的绩效数据为空');
        }

        $requiredFields = ['employee_number', 'evaluation_period', 'total_score'];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $rawData)) {
                throw new DataAccessException("外部系统缺少必需字段: {$field}");
            }
        }
    }

    private function normalizeStatus(string $status): string
    {
        return match (strtolower($status)) {
            'approved', '已审批' => 'approved',
            'pending', '待审批' => 'pending',
            'draft', '草稿' => 'draft',
            'rejected', '已拒绝' => 'rejected',
            default => 'unknown',
        };
    }

    /**
     * 从数组中提取浮点数值
     *
     * @param array<string, mixed> $data
     */
    private function extractFloatValue(array $data, string $key): float
    {
        $value = $data[$key] ?? 0;

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
