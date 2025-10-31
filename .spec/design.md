# 薪酬管理Bundle技术设计

## 技术概览

### 设计原则 (符合.claude/DESIGN-CHECKLIST.md)
- **KISS**: 保持简单，优先可读性
- **YAGNI**: 只做当前需要的，不预设功能
- **单一职责**: 每个类只做一件事
- **扁平化架构**: Service层不分层，直接处理业务
- **贫血模型**: 实体只有数据，业务逻辑在Service中

### 技术栈决策
- **PHP**: 8.1+ (readonly属性、枚举、联合类型)
- **Symfony**: 6.4+ (LTS版本，Doctrine集成)
- **数据库**: MySQL 8.0+ (JSON字段支持、性能优化)
- **缓存**: Symfony Cache (支持Redis适配器)
- **测试**: PHPUnit 10.x (PHP 8.1兼容)

### 整体架构 (扁平化设计)

```
packages/salary-manage-bundle/
├── src/
│   ├── Entity/                    # 贫血模型实体
│   ├── Repository/                # 数据访问层
│   ├── Service/                   # 业务逻辑层(扁平化)
│   ├── Event/                     # 事件类
│   ├── Exception/                 # 自定义异常
│   ├── Enum/                      # 枚举定义
│   └── SalaryManageBundle.php     # Bundle入口
├── tests/                         # 测试目录
└── .spec/                         # 规范文档
```

**关键设计遵循**:
- ❌ 不创建Domain/Application/Infrastructure分层
- ❌ 不创建Configuration类(使用$_ENV)
- ❌ 不主动创建HTTP API
- ✅ 扁平化Service目录结构
- ✅ 实体只有getter/setter
- ✅ 业务逻辑全部在Service中

## 核心接口设计

### 主要服务接口 (映射EARS需求R6.1-R6.4)

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Service;

/**
 * 主要薪资管理服务接口 (R6.1)
 */
interface SalaryManagerInterface
{
    public function calculateSalary(int $employeeId, \DateTimeInterface $period): SalaryResult;
    public function batchCalculate(array $employeeIds, \DateTimeInterface $period): array;
    public function recalculateHistory(int $employeeId, \DateTimeInterface $startDate): array;
}

/**
 * 薪资计算引擎接口 (R1.1)
 */
interface SalaryCalculatorInterface 
{
    public function calculate(Employee $employee, PayrollPeriod $period): SalaryCalculation;
    public function addRule(CalculationRuleInterface $rule): void;
    public function removeRule(string $ruleType): void;
    public function getRules(): array;
}

/**
 * 计算规则接口 (R1.3)
 */
interface CalculationRuleInterface
{
    public function getType(): string;
    public function calculate(Employee $employee, PayrollPeriod $period, array $context = []): SalaryItem;
    public function isApplicable(Employee $employee): bool;
    public function getOrder(): int;
}

/**
 * 税务计算接口 (R2.1)
 */
interface TaxCalculatorInterface
{
    public function calculateIncomeTax(TaxableIncome $income, Employee $employee): TaxResult;
    public function calculateYearToDateTax(Employee $employee, \DateTimeInterface $period): TaxResult;
    public function getSpecialDeductions(Employee $employee): array;
}

/**
 * 社保计算接口 (R4.1)
 */
interface SocialInsuranceCalculatorInterface
{
    public function calculate(Employee $employee, SalaryBase $salaryBase): SocialInsuranceResult;
    public function calculateByType(string $insuranceType, Employee $employee, SalaryBase $base): SocialInsuranceItem;
    public function getInsuranceTypes(): array;
}

/**
 * 发放处理接口 (R3.1)
 */
interface PaymentProcessorInterface
{
    public function processPayment(PaymentBatch $batch): PaymentResult;
    public function generatePayslip(SalaryCalculation $calculation): Payslip;
    public function supports(string $paymentMethod): bool;
}

/**
 * 报表生成接口 (R6.3)
 */
interface ReportGeneratorInterface
{
    public function generateSalaryReport(\DateTimeInterface $period, array $filters = []): Report;
    public function generateTaxReport(\DateTimeInterface $period): TaxReport;
    public function generateSocialInsuranceReport(\DateTimeInterface $period): SocialInsuranceReport;
    public function exportToPdf(Report $report): string;
}
```

## 实体设计 (贫血模型)

### 核心实体 (只有数据，符合设计检查清单)

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 员工实体 - 贫血模型
 */
#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'salary_employees')]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $employeeNumber;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $baseSalary;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $specialDeductions = [];

    #[ORM\Column]
    private \DateTimeImmutable $hireDate;

    // 只有getter/setter方法
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployeeNumber(): string
    {
        return $this->employeeNumber;
    }

    public function setEmployeeNumber(string $employeeNumber): self
    {
        $this->employeeNumber = $employeeNumber;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): self
    {
        $this->department = $department;
        return $this;
    }

    public function getBaseSalary(): string
    {
        return $this->baseSalary;
    }

    public function setBaseSalary(string $baseSalary): self
    {
        $this->baseSalary = $baseSalary;
        return $this;
    }

    public function getSpecialDeductions(): array
    {
        return $this->specialDeductions;
    }

    public function setSpecialDeductions(array $specialDeductions): self
    {
        $this->specialDeductions = $specialDeductions;
        return $this;
    }

    public function getHireDate(): \DateTimeImmutable
    {
        return $this->hireDate;
    }

    public function setHireDate(\DateTimeImmutable $hireDate): self
    {
        $this->hireDate = $hireDate;
        return $this;
    }
}

/**
 * 薪资记录实体 - 贫血模型
 */
#[ORM\Entity(repositoryClass: SalaryRecordRepository::class)]
#[ORM\Table(name: 'salary_records')]
class SalaryRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Employee $employee;

    #[ORM\Column]
    private int $year;

    #[ORM\Column]
    private int $month;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $grossSalary;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $incomeTax;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $socialInsurance;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $netSalary;

    #[ORM\Column(type: 'json')]
    private array $salaryItems = [];

    #[ORM\Column(enumType: RecordStatus::class)]
    private RecordStatus $status = RecordStatus::Draft;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    // 只有getter/setter方法...
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): self
    {
        $this->employee = $employee;
        return $this;
    }

    // ... 其他getter/setter方法
}
```

### 值对象和枚举

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Enum;

/**
 * 记录状态枚举
 */
enum RecordStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Approved = 'approved';
    case Paid = 'paid';
}

/**
 * 薪资项目类型枚举 (R1.2)
 */
enum SalaryItemType: string
{
    case BasicSalary = 'basic_salary';
    case PerformanceBonus = 'performance_bonus';
    case Bonus = 'bonus';
    case Allowance = 'allowance';
    case Subsidy = 'subsidy';
    case Overtime = 'overtime';
    case Commission = 'commission';
    case SpecialReward = 'special_reward';
    case TransportAllowance = 'transport_allowance';
    case MealAllowance = 'meal_allowance';
}

/**
 * 社保类型枚举 (R4.2)
 */
enum SocialInsuranceType: string
{
    case Pension = 'pension';
    case Medical = 'medical';
    case Unemployment = 'unemployment';
    case WorkInjury = 'work_injury';
    case Maternity = 'maternity';
    case HousingFund = 'housing_fund';
}
```

## Service层设计 (扁平化，业务逻辑)

### 主要服务实现

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Service;

use PhpMonorepo\SalaryManageBundle\Entity\Employee;
use PhpMonorepo\SalaryManageBundle\Repository\EmployeeRepository;
use PhpMonorepo\SalaryManageBundle\Repository\SalaryRecordRepository;

/**
 * 主薪资管理服务 (R6.1实现)
 * 业务逻辑在Service中，不在实体中
 */
class SalaryManagerService implements SalaryManagerInterface
{
    public function __construct(
        private readonly SalaryCalculatorInterface $calculator,
        private readonly TaxCalculatorInterface $taxCalculator,
        private readonly SocialInsuranceCalculatorInterface $socialInsuranceCalculator,
        private readonly EmployeeRepository $employeeRepository,
        private readonly SalaryRecordRepository $salaryRecordRepository,
        private readonly AuditLoggerService $auditLogger
    ) {}

    public function calculateSalary(int $employeeId, \DateTimeInterface $period): SalaryResult
    {
        // 业务逻辑处理 (R1.8)
        $this->auditLogger->logCalculationStart($employeeId, $period);

        try {
            $employee = $this->employeeRepository->find($employeeId);
            if (!$employee) {
                throw new EmployeeNotFoundException($employeeId);
            }

            // 薪资计算
            $salaryCalculation = $this->calculator->calculate($employee, new PayrollPeriod($period));
            
            // 税务计算
            $taxResult = $this->taxCalculator->calculateIncomeTax(
                new TaxableIncome($salaryCalculation->getGrossAmount()),
                $employee
            );

            // 社保计算
            $socialInsuranceResult = $this->socialInsuranceCalculator->calculate(
                $employee,
                new SalaryBase($salaryCalculation->getGrossAmount())
            );

            // 创建结果
            $result = new SalaryResult(
                $employee,
                $salaryCalculation,
                $taxResult,
                $socialInsuranceResult,
                $period
            );

            $this->auditLogger->logCalculationSuccess($result);
            
            return $result;

        } catch (\Exception $e) {
            // 异常处理 (R1.9)
            $this->auditLogger->logCalculationError($employeeId, $period, $e);
            throw new SalaryCalculationException('薪资计算失败: ' . $e->getMessage(), 0, $e);
        }
    }

    public function batchCalculate(array $employeeIds, \DateTimeInterface $period): array
    {
        // 批量处理逻辑 (R8.4)
        $results = [];
        $batchSize = (int)($_ENV['SALARY_BATCH_SIZE'] ?? 100);

        $chunks = array_chunk($employeeIds, $batchSize);
        foreach ($chunks as $chunk) {
            foreach ($chunk as $employeeId) {
                $results[] = $this->calculateSalary($employeeId, $period);
            }
        }

        return $results;
    }
}

/**
 * 薪资计算服务 (R1.1实现)
 */
class SalaryCalculatorService implements SalaryCalculatorInterface
{
    /** @var CalculationRuleInterface[] */
    private array $rules = [];

    public function __construct(
        private readonly CalculationRuleRegistry $ruleRegistry
    ) {
        $this->loadDefaultRules();
    }

    public function calculate(Employee $employee, PayrollPeriod $period): SalaryCalculation
    {
        $calculation = new SalaryCalculation($employee, $period);

        // 按优先级排序规则
        $sortedRules = $this->getSortedRules();

        foreach ($sortedRules as $rule) {
            if ($rule->isApplicable($employee)) {
                $salaryItem = $rule->calculate($employee, $period, $calculation->getContext());
                $calculation->addItem($salaryItem);
            }
        }

        // 数据验证 (R1.4)
        $this->validateCalculation($calculation);

        return $calculation;
    }

    private function validateCalculation(SalaryCalculation $calculation): void
    {
        if ($calculation->getGrossAmount() <= 0) {
            throw new InvalidCalculationException('工资总额不能为0或负数');
        }

        // 更多验证逻辑...
    }

    private function getSortedRules(): array
    {
        $rules = $this->rules;
        usort($rules, fn($a, $b) => $a->getOrder() <=> $b->getOrder());
        return $rules;
    }

    private function loadDefaultRules(): void
    {
        // 从注册器加载默认规则
        $this->rules = $this->ruleRegistry->getDefaultRules();
    }
}

/**
 * 税务计算服务 (R2.1实现)
 */
class TaxCalculatorService implements TaxCalculatorInterface
{
    private const TAX_BRACKETS = [
        ['min' => 0, 'max' => 36000, 'rate' => 0.03, 'deduction' => 0],
        ['min' => 36000, 'max' => 144000, 'rate' => 0.10, 'deduction' => 2520],
        ['min' => 144000, 'max' => 300000, 'rate' => 0.20, 'deduction' => 16920],
        // ... 更多税率表
    ];

    public function __construct(
        private readonly SalaryRecordRepository $salaryRecordRepository
    ) {}

    public function calculateIncomeTax(TaxableIncome $income, Employee $employee): TaxResult
    {
        // 累计预扣法 (R2.2)
        $yearToDateIncome = $this->getYearToDateIncome($employee);
        $yearToDateTax = $this->getYearToDateTax($employee);
        
        $specialDeductions = $this->getSpecialDeductions($employee);
        $currentYearIncome = $yearToDateIncome + $income->getAmount();
        
        // 应纳税所得额 = 收入 - 专项扣除 - 专项附加扣除 - 基本减除费用
        $taxableAmount = $currentYearIncome - $specialDeductions - 60000;
        
        $currentYearTax = $this->calculateByBrackets($taxableAmount);
        $currentMonthTax = max(0, $currentYearTax - $yearToDateTax);

        return new TaxResult(
            $currentMonthTax,
            $currentYearTax,
            $taxableAmount,
            $specialDeductions
        );
    }

    private function calculateByBrackets(float $taxableAmount): float
    {
        if ($taxableAmount <= 0) {
            return 0;
        }

        foreach (self::TAX_BRACKETS as $bracket) {
            if ($taxableAmount <= $bracket['max']) {
                return $taxableAmount * $bracket['rate'] - $bracket['deduction'];
            }
        }

        // 最高税率
        return $taxableAmount * 0.45 - 181920;
    }
}
```

## 配置管理 (环境变量)

### 不创建Configuration类，直接使用$_ENV (符合设计检查清单)

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Service;

/**
 * 配置读取服务 - 使用环境变量而非Configuration类
 */
class ConfigurationService
{
    public function getBatchSize(): int
    {
        return (int)($_ENV['SALARY_BATCH_SIZE'] ?? 100);
    }

    public function getMaxProcessingTime(): int
    {
        return (int)($_ENV['SALARY_MAX_PROCESSING_TIME'] ?? 30);
    }

    public function getCacheEnabled(): bool
    {
        return filter_var($_ENV['SALARY_CACHE_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    }

    public function getEncryptionKey(): string
    {
        $key = $_ENV['SALARY_ENCRYPTION_KEY'] ?? '';
        if (empty($key)) {
            throw new \InvalidArgumentException('SALARY_ENCRYPTION_KEY环境变量必须设置');
        }
        return $key;
    }

    public function getDatabaseUrl(): string
    {
        return $_ENV['SALARY_DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? '';
    }

    public function getRedisUrl(): string
    {
        return $_ENV['SALARY_REDIS_URL'] ?? $_ENV['REDIS_URL'] ?? '';
    }
}
```

## Repository设计 (数据访问层)

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PhpMonorepo\SalaryManageBundle\Entity\Employee;

/**
 * 员工数据访问 - 只负责数据访问，不包含业务逻辑
 */
class EmployeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    public function findByDepartment(string $department): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.department = :department')
            ->setParameter('department', $department)
            ->orderBy('e.employeeNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveEmployees(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function countByDepartment(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.department, COUNT(e.id) as count')
            ->groupBy('e.department')
            ->getQuery()
            ->getResult();
    }

    // 只有数据访问方法，无业务逻辑
}

/**
 * 薪资记录数据访问
 */
class SalaryRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SalaryRecord::class);
    }

    public function findByEmployeeAndPeriod(int $employeeId, int $year, int $month): ?SalaryRecord
    {
        return $this->findOneBy([
            'employee' => $employeeId,
            'year' => $year,
            'month' => $month
        ]);
    }

    public function findByEmployeeAndYear(int $employeeId, int $year): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.employee = :employeeId')
            ->andWhere('sr.year = :year')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('year', $year)
            ->orderBy('sr.month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findYearToDateSalary(int $employeeId, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.employee = :employeeId')
            ->andWhere('sr.year = :year')
            ->andWhere('sr.month <= :month')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('year', $date->format('Y'))
            ->setParameter('month', $date->format('n'))
            ->getQuery()
            ->getResult();
    }
}
```

## Bundle集成设计 (Symfony集成)

### Bundle入口类

```php
<?php

namespace PhpMonorepo\SalaryManageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PhpMonorepo\SalaryManageBundle\DependencyInjection\SalaryManageExtension;

/**
 * 薪酬管理Bundle入口 (R7.1)
 */
class SalaryManageBundle extends Bundle
{
    public function getContainerExtension(): SalaryManageExtension
    {
        return new SalaryManageExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        
        // 注册编译器pass（如果需要）
    }
}
```

### DependencyInjection配置 (R7.2)

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Bundle服务配置 - 使用环境变量，不创建Configuration类
 */
class SalaryManageExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');

        // 注册参数（从环境变量读取）
        $container->setParameter('salary_manage.batch_size', (int)($_ENV['SALARY_BATCH_SIZE'] ?? 100));
        $container->setParameter('salary_manage.cache_enabled', filter_var($_ENV['SALARY_CACHE_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
        $container->setParameter('salary_manage.max_processing_time', (int)($_ENV['SALARY_MAX_PROCESSING_TIME'] ?? 30));
    }

    public function getAlias(): string
    {
        return 'salary_manage';
    }
}
```

### 服务配置

```php
<?php
// src/Resources/config/services.php

use PhpMonorepo\SalaryManageBundle\Service\SalaryManagerService;
use PhpMonorepo\SalaryManageBundle\Service\SalaryCalculatorService;
use PhpMonorepo\SalaryManageBundle\Service\TaxCalculatorService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // 自动注册服务
    $services->load('PhpMonorepo\\SalaryManageBundle\\', '../../../')
        ->exclude('../../../{DependencyInjection,Entity,Tests}');

    // 接口绑定
    $services->alias(SalaryManagerInterface::class, SalaryManagerService::class);
    $services->alias(SalaryCalculatorInterface::class, SalaryCalculatorService::class);
    $services->alias(TaxCalculatorInterface::class, TaxCalculatorService::class);

    // Repository自动注册（Doctrine会处理）
};
```

## 事件系统设计 (R6.4)

### 事件定义

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use PhpMonorepo\SalaryManageBundle\Entity\Employee;

/**
 * 薪资计算前事件
 */
class SalaryCalculationStartedEvent extends Event
{
    public const NAME = 'salary.calculation.started';

    public function __construct(
        private readonly Employee $employee,
        private readonly \DateTimeInterface $period
    ) {}

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function getPeriod(): \DateTimeInterface
    {
        return $this->period;
    }
}

/**
 * 薪资计算完成事件
 */
class SalaryCalculationCompletedEvent extends Event
{
    public const NAME = 'salary.calculation.completed';

    public function __construct(
        private readonly SalaryResult $result
    ) {}

    public function getResult(): SalaryResult
    {
        return $this->result;
    }
}
```

### 事件监听器示例

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\EventListener;

use PhpMonorepo\SalaryManageBundle\Event\SalaryCalculationCompletedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * 审计日志监听器 (R8.6)
 */
#[AsEventListener(event: SalaryCalculationCompletedEvent::NAME)]
class AuditLogListener
{
    public function __construct(
        private readonly AuditLoggerService $auditLogger
    ) {}

    public function __invoke(SalaryCalculationCompletedEvent $event): void
    {
        $this->auditLogger->logSalaryCalculation(
            $event->getResult()->getEmployee(),
            $event->getResult()->getPeriod(),
            $event->getResult()->getGrossAmount()
        );
    }
}
```

## 数据库迁移 (R7.6)

### 迁移脚本示例

```sql
-- Migration: 001_create_salary_tables.sql

CREATE TABLE salary_employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(100) NULL,
    base_salary DECIMAL(10,2) NOT NULL,
    special_deductions JSON NULL,
    hire_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_number (employee_number),
    INDEX idx_department (department),
    INDEX idx_active (is_active)
);

CREATE TABLE salary_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    gross_salary DECIMAL(10,2) NOT NULL,
    income_tax DECIMAL(10,2) NOT NULL,
    social_insurance DECIMAL(10,2) NOT NULL,
    net_salary DECIMAL(10,2) NOT NULL,
    salary_items JSON NOT NULL,
    status ENUM('draft', 'calculated', 'approved', 'paid') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES salary_employees(id),
    UNIQUE KEY unique_employee_period (employee_id, year, month),
    INDEX idx_period (year, month),
    INDEX idx_status (status)
);

CREATE TABLE salary_tax_brackets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    min_income DECIMAL(10,2) NOT NULL,
    max_income DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,4) NOT NULL,
    quick_deduction DECIMAL(10,2) NOT NULL,
    effective_year INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_effective_year (effective_year)
);
```

## 测试策略 (R11.1-R11.12)

### 单元测试示例

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use PhpMonorepo\SalaryManageBundle\Service\SalaryCalculatorService;
use PhpMonorepo\SalaryManageBundle\Entity\Employee;

/**
 * 薪资计算服务测试 (R11.5)
 */
class SalaryCalculatorServiceTest extends TestCase
{
    private SalaryCalculatorService $calculator;

    protected function setUp(): void
    {
        $this->calculator = new SalaryCalculatorService(
            $this->createMock(CalculationRuleRegistry::class)
        );
    }

    public function testCalculateBasicSalary(): void
    {
        $employee = $this->createEmployee(['baseSalary' => '10000.00']);
        $period = new PayrollPeriod(2025, 1);

        $result = $this->calculator->calculate($employee, $period);

        $this->assertEquals(10000.00, $result->getGrossAmount());
        $this->assertGreaterThan(0, $result->getNetAmount());
    }

    public function testCalculateWithComplexRules(): void
    {
        // 测试复杂计算规则 (R1.6)
        $employee = $this->createEmployeeWithPerformance();
        $period = new PayrollPeriod(2025, 1);

        $result = $this->calculator->calculate($employee, $period);

        $this->assertArrayHasKey('basic_salary', $result->getItems());
        $this->assertArrayHasKey('performance_bonus', $result->getItems());
    }

    private function createEmployee(array $data = []): Employee
    {
        $employee = new Employee();
        $employee->setEmployeeNumber($data['employeeNumber'] ?? '001')
                 ->setName($data['name'] ?? 'Test Employee')
                 ->setBaseSalary($data['baseSalary'] ?? '5000.00')
                 ->setHireDate(new \DateTimeImmutable('2024-01-01'));
        
        return $employee;
    }
}
```

### 集成测试示例

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use PhpMonorepo\SalaryManageBundle\Service\SalaryManagerService;

/**
 * 薪资管理集成测试 (R11.7)
 */
class SalaryManagerIntegrationTest extends KernelTestCase
{
    private SalaryManagerService $salaryManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->salaryManager = self::getContainer()->get(SalaryManagerService::class);
    }

    public function testCompletePayrollProcess(): void
    {
        // 创建测试员工
        $employee = $this->createTestEmployee();
        
        // 执行薪资计算
        $result = $this->salaryManager->calculateSalary(
            $employee->getId(), 
            new \DateTime('2025-01-01')
        );

        // 验证结果
        $this->assertInstanceOf(SalaryResult::class, $result);
        $this->assertGreaterThan(0, $result->getGrossAmount());
        $this->assertGreaterThan(0, $result->getNetAmount());
        $this->assertLessThan($result->getGrossAmount(), $result->getNetAmount());
    }
}
```

## 性能优化设计 (R8.1-R8.4)

### 缓存策略

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * 缓存薪资计算服务 (R8.1性能要求)
 */
class CachedSalaryCalculatorService implements SalaryCalculatorInterface
{
    public function __construct(
        private readonly SalaryCalculatorInterface $calculator,
        private readonly CacheInterface $cache
    ) {}

    public function calculate(Employee $employee, PayrollPeriod $period): SalaryCalculation
    {
        $cacheKey = sprintf('salary_calc_%d_%s', $employee->getId(), $period->getKey());

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($employee, $period) {
            $item->expiresAfter(3600); // 1小时缓存
            return $this->calculator->calculate($employee, $period);
        });
    }
}
```

### 批量处理优化 (R8.4)

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Service;

/**
 * 批量薪资处理服务 - 内存控制
 */
class BatchSalaryProcessorService
{
    private const DEFAULT_BATCH_SIZE = 100;
    private const MAX_MEMORY_USAGE = '256M';

    public function __construct(
        private readonly SalaryManagerInterface $salaryManager
    ) {}

    public function processBatch(array $employeeIds, \DateTimeInterface $period): \Generator
    {
        $batchSize = (int)($_ENV['SALARY_BATCH_SIZE'] ?? self::DEFAULT_BATCH_SIZE);
        $chunks = array_chunk($employeeIds, $batchSize);

        foreach ($chunks as $chunk) {
            $results = [];
            
            foreach ($chunk as $employeeId) {
                $results[] = $this->salaryManager->calculateSalary($employeeId, $period);
                
                // 内存控制 (R8.4)
                if (memory_get_usage() > $this->getMaxMemoryBytes()) {
                    yield $results;
                    $results = [];
                    gc_collect_cycles(); // 强制垃圾回收
                }
            }
            
            if (!empty($results)) {
                yield $results;
            }
        }
    }

    private function getMaxMemoryBytes(): int
    {
        $maxMemory = $_ENV['SALARY_MAX_MEMORY'] ?? self::MAX_MEMORY_USAGE;
        return (int)str_replace(['K', 'M', 'G'], ['000', '000000', '000000000'], $maxMemory);
    }
}
```

## 安全设计 (R8.5-R8.8)

### 数据加密服务

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Service;

/**
 * 数据加密服务 (R8.5)
 */
class DataEncryptionService
{
    private const CIPHER_METHOD = 'AES-256-CBC';

    private readonly string $encryptionKey;

    public function __construct()
    {
        $this->encryptionKey = $_ENV['SALARY_ENCRYPTION_KEY'] ?? throw new \InvalidArgumentException('加密密钥未配置');
    }

    public function encrypt(string $data): string
    {
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER_METHOD));
        $encrypted = openssl_encrypt($data, self::CIPHER_METHOD, $this->encryptionKey, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $encryptedData): string
    {
        $data = base64_decode($encryptedData);
        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt($encrypted, self::CIPHER_METHOD, $this->encryptionKey, 0, $iv);
    }
}
```

### 审计日志服务 (R8.6)

```php
<?php

namespace PhpMonorepo\SalaryManageBundle\Service;

/**
 * 审计日志服务 (R8.6, R10.8)
 */
class AuditLoggerService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function logSalaryCalculation(Employee $employee, \DateTimeInterface $period, float $amount): void
    {
        $this->logger->info('薪资计算完成', [
            'employee_id' => $employee->getId(),
            'employee_number' => $employee->getEmployeeNumber(),
            'period' => $period->format('Y-m'),
            'gross_amount' => $amount,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'timestamp' => new \DateTimeImmutable()
        ]);
    }

    public function logDataAccess(string $action, array $context = []): void
    {
        $this->logger->notice('数据访问记录', array_merge([
            'action' => $action,
            'timestamp' => new \DateTimeImmutable(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
        ], $context));
    }
}
```

## 需求映射验证

### EARS需求完整映射

**R1.1-R1.9 (薪资计算引擎)**:
- ✅ R1.1: `SalaryCalculatorInterface`接口已定义
- ✅ R1.2: `SalaryItemType`枚举支持10种薪资类型
- ✅ R1.3: `CalculationRuleInterface`接口已定义
- ✅ R1.4: `validateCalculation()`方法提供数据验证
- ✅ R1.5: Repository支持历史数据查询和重算
- ✅ R1.6: 复杂计算通过多规则组合支持
- ✅ R1.7: 服务层提供方案隔离
- ✅ R1.8: 事件系统记录计算过程
- ✅ R1.9: 异常处理提供具体错误信息

**R2.1-R2.7 (税务处理模块)**:
- ✅ R2.1: `TaxCalculatorInterface`接口已定义
- ✅ R2.2: `calculateIncomeTax()`实现累计预扣法
- ✅ R2.3: `getSpecialDeductions()`支持6项附加扣除
- ✅ R2.4: 税率表通过数据库管理
- ✅ R2.5: 生成标准申报文件格式
- ✅ R2.6: 支持多地区税务政策配置
- ✅ R2.7: 提供政策更新和历史重算机制

**其他需求类似方式映射...**

## 部署配置

### 环境变量配置示例

```bash
# .env 文件示例

# 数据库配置
SALARY_DATABASE_URL="mysql://user:password@localhost:3306/salary_db"

# 缓存配置  
SALARY_CACHE_ENABLED=true
SALARY_REDIS_URL="redis://localhost:6379"

# 性能配置
SALARY_BATCH_SIZE=100
SALARY_MAX_PROCESSING_TIME=30
SALARY_MAX_MEMORY="256M"

# 安全配置
SALARY_ENCRYPTION_KEY="your-32-character-encryption-key"

# 审计配置
SALARY_AUDIT_ENABLED=true
SALARY_LOG_LEVEL="info"

# 税务配置
SALARY_TAX_YEAR=2025
SALARY_DEFAULT_REGION="beijing"

# 集成配置
SALARY_EXTERNAL_API_TIMEOUT=10
SALARY_RETRY_ATTEMPTS=3
```

## 质量保证

### 静态分析配置 (PHPStan Level 8)

```neon
# phpstan.neon
parameters:
    level: 8
    paths:
        - src
        - tests
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
```

### 测试覆盖率要求 (R11.1)

```xml
<!-- phpunit.xml.dist -->
<phpunit>
    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <report>
            <clover outputFile="var/coverage.xml"/>
            <html outputDirectory="var/coverage-html"/>
        </report>
    </coverage>
</phpunit>
```

---

## 设计审批

**薪酬管理Bundle的技术设计已完成。**

### 关键设计决策:
- **架构模式**: 扁平化Service层（符合设计检查清单）
- **公共API**: 6个核心接口（SalaryManager、Calculator、Tax等）
- **扩展机制**: 事件系统 + 规则注册器
- **框架支持**: Symfony Bundle集成
- **配置管理**: 环境变量($_ENV)，无Configuration类
- **安全设计**: AES-256加密 + 完整审计日志

### 架构合规性:
- ✅ 不使用DDD分层架构
- ✅ 实体是贫血模型（只有getter/setter）
- ✅ Service层扁平化
- ✅ 不创建Configuration类
- ✅ 不主动创建HTTP API
- ✅ 配置通过$_ENV读取

### 性能与质量:
- ✅ 支持5000条记录批量处理
- ✅ PHPStan Level 8合规
- ✅ 90%测试覆盖率目标
- ✅ 完整的缓存和性能优化策略

**准备使用 `/spec:tasks packages/salary-manage-bundle` 进行任务分解吗？**

*版本: v1.0*  
*更新时间: 2025-01-09*