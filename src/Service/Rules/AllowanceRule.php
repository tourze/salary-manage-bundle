<?php

namespace Tourze\SalaryManageBundle\Service\Rules;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryItem;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;
use Tourze\SalaryManageBundle\Service\CalculationRuleInterface;

/**
 * 津贴计算规则
 * 计算员工的各项津贴（职位津贴、技能津贴、地区津贴等）
 */
class AllowanceRule implements CalculationRuleInterface
{
    public function getType(): string
    {
        return SalaryItemType::Allowance->value;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function calculate(Employee $employee, PayrollPeriod $period, array $context = []): SalaryItem
    {
        $allowances = $this->calculateAllowances($employee, $period, $context);
        $totalAmount = array_sum($allowances);

        $item = new SalaryItem();
        $item->setType(SalaryItemType::Allowance);
        $item->setAmount($totalAmount);
        $item->setDescription('津贴合计');
        $item->setMetadata([
            'employee_id' => $employee->getId(),
            'employee_number' => $employee->getEmployeeNumber(),
            'period' => $period->getKey(),
            'breakdown' => $allowances,
            'total_types' => count(array_filter($allowances, fn ($amount) => $amount > 0)),
        ]);

        return $item;
    }

    public function isApplicable(Employee $employee): bool
    {
        $config = $this->getAllowanceConfig($employee);
        $totalAllowance = $this->calculateTotalApplicableAllowance($config);

        return $totalAllowance > 0;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function calculateTotalApplicableAllowance(array $config): float
    {
        $rates = is_array($config['allowance_rates'] ?? null) ? $config['allowance_rates'] : [];

        $positionLevel = is_string($config['position_level'] ?? null) ? $config['position_level'] : 'junior';
        $skillLevel = is_string($config['skill_level'] ?? null) ? $config['skill_level'] : 'basic';
        $regionCode = is_string($config['region_code'] ?? null) ? $config['region_code'] : 'tier3';

        $positionAllowance = $this->extractAllowanceAmount($rates, 'position', $positionLevel);
        $skillAllowance = $this->extractAllowanceAmount($rates, 'skill', $skillLevel);
        $regionalAllowance = $this->extractAllowanceAmount($rates, 'regional', $regionCode);
        $educationAllowance = $this->extractAllowanceAmount($rates, 'education', 'bachelor');

        return $positionAllowance + $skillAllowance + $regionalAllowance + $educationAllowance;
    }

    /**
     * @param array<int|string, mixed> $rates
     */
    private function extractAllowanceAmount(array $rates, string $category, string $level): float
    {
        $categoryRates = $rates[$category] ?? null;
        if (!is_array($categoryRates)) {
            return 0.0;
        }

        $amount = $categoryRates[$level] ?? 0.0;

        return is_numeric($amount) ? (float) $amount : 0.0;
    }

    public function getOrder(): int
    {
        return 30; // 在基本工资后，加班费前计算
    }

    /**
     * 计算各项津贴金额
     * @param array<string, mixed> $context
     * @return array<string, float>
     */
    private function calculateAllowances(Employee $employee, PayrollPeriod $period, array $context = []): array
    {
        $config = $this->getAllowanceConfig($employee);
        $allowances = [];

        // 职位津贴 - 基于岗位级别
        $allowances['position_allowance'] = $this->calculatePositionAllowance($employee, $config);

        // 技能津贴 - 基于技能等级
        $allowances['skill_allowance'] = $this->calculateSkillAllowance($employee, $config);

        // 地区津贴 - 基于工作地点
        $allowances['regional_allowance'] = $this->calculateRegionalAllowance($employee, $config);

        // 学历津贴 - 基于学历水平
        $allowances['education_allowance'] = $this->calculateEducationAllowance($employee, $config);

        // 工龄津贴 - 基于工作年限
        $allowances['seniority_allowance'] = $this->calculateSeniorityAllowance($employee, $period, $config);

        // 特殊津贴 - 基于特殊岗位要求
        $allowances['special_allowance'] = $this->calculateSpecialAllowance($employee, $config);

        // 过滤掉为0的津贴项目
        return array_filter($allowances, fn (float $amount) => $amount > 0);
    }

    /**
     * 获取员工津贴配置
     * @return array<string, mixed>
     */
    private function getAllowanceConfig(Employee $employee): array
    {
        // 从员工的额外数据或配置表中获取津贴配置
        // 这里使用模拟数据，实际应用中应从数据库或配置服务获取
        return [
            'position_level' => $this->getEmployeePositionLevel($employee),
            'skill_level' => $this->getEmployeeSkillLevel($employee),
            'region_code' => $this->getEmployeeRegion($employee),
            'education_level' => $this->getEmployeeEducation($employee),
            'hire_date' => $employee->getHireDate(),
            'special_positions' => $this->getSpecialPositions($employee),
            'allowance_rates' => $this->getAllowanceRates(),
        ];
    }

    /**
     * 计算职位津贴
     * @param array<string, mixed> $config
     */
    private function calculatePositionAllowance(Employee $employee, array $config): float
    {
        $positionLevel = is_string($config['position_level'] ?? null) ? $config['position_level'] : 'junior';
        $allowanceRates = is_array($config['allowance_rates'] ?? null) ? $config['allowance_rates'] : [];
        $rates = is_array($allowanceRates['position'] ?? null) ? $allowanceRates['position'] : [];

        $amount = $rates[$positionLevel] ?? 0.0;

        return is_numeric($amount) ? (float) $amount : 0.0;
    }

    /**
     * 计算技能津贴
     * @param array<string, mixed> $config
     */
    private function calculateSkillAllowance(Employee $employee, array $config): float
    {
        $skillLevel = is_string($config['skill_level'] ?? null) ? $config['skill_level'] : 'basic';
        $allowanceRates = is_array($config['allowance_rates'] ?? null) ? $config['allowance_rates'] : [];
        $rates = is_array($allowanceRates['skill'] ?? null) ? $allowanceRates['skill'] : [];

        $amount = $rates[$skillLevel] ?? 0.0;

        return is_numeric($amount) ? (float) $amount : 0.0;
    }

    /**
     * 计算地区津贴
     * @param array<string, mixed> $config
     */
    private function calculateRegionalAllowance(Employee $employee, array $config): float
    {
        $regionCode = is_string($config['region_code'] ?? null) ? $config['region_code'] : 'tier3';
        $allowanceRates = is_array($config['allowance_rates'] ?? null) ? $config['allowance_rates'] : [];
        $rates = is_array($allowanceRates['regional'] ?? null) ? $allowanceRates['regional'] : [];

        $amount = $rates[$regionCode] ?? 0.0;

        return is_numeric($amount) ? (float) $amount : 0.0;
    }

    /**
     * 计算学历津贴
     * @param array<string, mixed> $config
     */
    private function calculateEducationAllowance(Employee $employee, array $config): float
    {
        $educationLevel = is_string($config['education_level'] ?? null) ? $config['education_level'] : 'bachelor';
        $allowanceRates = is_array($config['allowance_rates'] ?? null) ? $config['allowance_rates'] : [];
        $rates = is_array($allowanceRates['education'] ?? null) ? $allowanceRates['education'] : [];

        $amount = $rates[$educationLevel] ?? 0.0;

        return is_numeric($amount) ? (float) $amount : 0.0;
    }

    /**
     * 计算工龄津贴
     * @param array<string, mixed> $config
     */
    private function calculateSeniorityAllowance(Employee $employee, PayrollPeriod $period, array $config): float
    {
        $workYears = $this->calculateWorkYears($config['hire_date'] ?? null, $period);
        if (null === $workYears) {
            return 0.0;
        }

        $seniorityLevel = $this->determineSeniorityLevel($workYears);
        if (null === $seniorityLevel) {
            return 0.0;
        }

        return $this->getSeniorityAllowanceAmount($config, $seniorityLevel);
    }

    private function calculateWorkYears(mixed $hireDate, PayrollPeriod $period): ?int
    {
        if (!$hireDate instanceof \DateTimeImmutable) {
            return null;
        }

        $currentDate = new \DateTimeImmutable($period->getYear() . '-' . $period->getMonth() . '-01');

        return $currentDate->diff($hireDate)->y;
    }

    private function determineSeniorityLevel(int $workYears): ?string
    {
        return match (true) {
            $workYears >= 10 => 'senior',
            $workYears >= 5 => 'experienced',
            $workYears >= 2 => 'intermediate',
            $workYears >= 1 => 'junior',
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $config
     */
    private function getSeniorityAllowanceAmount(array $config, string $level): float
    {
        $allowanceRates = is_array($config['allowance_rates'] ?? null) ? $config['allowance_rates'] : [];
        $rates = is_array($allowanceRates['seniority'] ?? null) ? $allowanceRates['seniority'] : [];

        $amount = $rates[$level] ?? 0.0;

        return is_numeric($amount) ? (float) $amount : 0.0;
    }

    /**
     * 计算特殊津贴
     * @param array<string, mixed> $config
     */
    private function calculateSpecialAllowance(Employee $employee, array $config): float
    {
        $specialPositions = $config['special_positions'] ?? [];
        if (!is_array($specialPositions)) {
            return 0.0;
        }

        $allowanceRates = is_array($config['allowance_rates'] ?? null) ? $config['allowance_rates'] : [];
        $rates = is_array($allowanceRates['special'] ?? null) ? $allowanceRates['special'] : [];

        $totalSpecialAllowance = 0.0;
        foreach ($specialPositions as $position) {
            if (!is_string($position)) {
                continue;
            }
            $amount = $rates[$position] ?? 0.0;
            $totalSpecialAllowance += is_numeric($amount) ? (float) $amount : 0.0;
        }

        return $totalSpecialAllowance;
    }

    /**
     * 获取员工职位级别
     */
    private function getEmployeePositionLevel(Employee $employee): string
    {
        // 实际应用中应从员工档案或岗位表中获取
        // 这里基于部门做简单映射
        $department = $employee->getDepartment();

        return match ($department) {
            '总经理办公室', '董事会' => 'executive',
            '技术部', '研发部' => 'senior',
            '市场部', '销售部' => 'middle',
            '行政部', '人事部' => 'junior',
            default => 'junior',
        };
    }

    /**
     * 获取员工技能等级
     */
    private function getEmployeeSkillLevel(Employee $employee): string
    {
        // 实际应用中应从技能评估表或认证记录中获取
        // 这里基于基本薪资做简单推算
        $baseSalary = (float) $employee->getBaseSalary();

        if ($baseSalary >= 20000) {
            return 'expert';
        }
        if ($baseSalary >= 15000) {
            return 'advanced';
        }
        if ($baseSalary >= 10000) {
            return 'intermediate';
        }

        return 'basic';
    }

    /**
     * 获取员工工作地区
     */
    private function getEmployeeRegion(Employee $employee): string
    {
        // 实际应用中应从员工档案中获取工作地点
        // 这里使用默认值
        return 'tier2'; // 二线城市
    }

    /**
     * 获取员工学历水平
     */
    private function getEmployeeEducation(Employee $employee): string
    {
        // 实际应用中应从员工档案中获取学历信息
        // 这里使用默认值
        return 'bachelor'; // 本科
    }

    /**
     * 获取员工特殊岗位
     * @return array<int, string>
     */
    private function getSpecialPositions(Employee $employee): array
    {
        // 实际应用中应从岗位配置中获取
        // 这里基于部门做简单映射
        $department = $employee->getDepartment();

        $specialPositions = [];

        // 技术类岗位可能有技术津贴
        if (null !== $department && (str_contains($department, '技术') || str_contains($department, '研发'))) {
            $specialPositions[] = 'technical';
        }

        // 管理岗位可能有管理津贴
        if (null !== $department && (str_contains($department, '经理') || str_contains($department, '总监'))) {
            $specialPositions[] = 'management';
        }

        return $specialPositions;
    }

    /**
     * 获取津贴费率标准
     * @return array<string, array<string, float>>
     */
    private function getAllowanceRates(): array
    {
        // 实际应用中应从配置表或配置文件中读取
        return [
            'position' => [
                'executive' => 5000.0,  // 高管津贴
                'senior' => 2000.0,     // 高级岗位津贴
                'middle' => 1000.0,     // 中级岗位津贴
                'junior' => 500.0,      // 初级岗位津贴
            ],
            'skill' => [
                'expert' => 3000.0,     // 专家级技能津贴
                'advanced' => 2000.0,   // 高级技能津贴
                'intermediate' => 1000.0, // 中级技能津贴
                'basic' => 0.0,         // 基础技能无津贴
            ],
            'regional' => [
                'tier1' => 2000.0,      // 一线城市津贴
                'tier2' => 1000.0,      // 二线城市津贴
                'tier3' => 500.0,       // 三线城市津贴
                'rural' => 800.0,       // 乡村津贴
            ],
            'education' => [
                'phd' => 2000.0,        // 博士津贴
                'master' => 1000.0,     // 硕士津贴
                'bachelor' => 500.0,    // 本科津贴
                'associate' => 200.0,   // 专科津贴
                'highschool' => 0.0,    // 高中无津贴
            ],
            'seniority' => [
                'senior' => 1500.0,     // 10年以上工龄津贴
                'experienced' => 1000.0, // 5-10年工龄津贴
                'intermediate' => 600.0, // 2-5年工龄津贴
                'junior' => 300.0,      // 1-2年工龄津贴
            ],
            'special' => [
                'technical' => 1000.0,   // 技术岗位津贴
                'management' => 1500.0,  // 管理岗位津贴
                'hazardous' => 800.0,    // 高危岗位津贴
                'remote' => 600.0,       // 偏远地区津贴
            ],
        ];
    }
}
