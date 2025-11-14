# Salary Manage Bundle

[English](README.md) | [中文](README.zh-CN.md)

企业薪酬管理 Symfony Bundle - 提供薪资计算、税务处理、社保管理的完整解决方案

## ✨ 功能特性

- **💰 薪资计算**：灵活的薪资计算引擎，支持可配置规则和多种薪酬组成部分
- **🧾 税务管理**：自动化税务计算，支持多个税级和地区性税收政策
- **🏥 社保管理**：全面的社保计算，包括养老、医疗、失业、工伤、生育保险
- **📊 报表系统**：高级报表系统，支持多种报表类型和导出功能
- **🔄 工作流**：薪资计算和付款的审批工作流系统
- **💳 支付处理**：集成支付处理，支持多种支付方式和状态跟踪
- **📥 数据导入导出**：灵活的数据导入导出功能，支持与外部系统集成
- **🎯 绩效集成**：绩效数据集成，支持基于绩效的薪资计算
- **⏰ 考勤集成**：考勤数据集成，支持基于考勤的薪资计算

## 📋 系统要求

- PHP ^8.2
- Symfony ^7.3
- Doctrine ORM ^3.0

## 🚀 安装

```bash
composer require tourze/salary-manage-bundle
```

## ⚙️ 配置

在您的 Symfony 应用中启用 Bundle：

```php
// config/bundles.php
return [
    // ...
    Tourze\SalaryManageBundle\SalaryManageBundle::class => ['all' => true],
];
```

## 📖 基本使用

### 薪资计算

```php
use Tourze\SalaryManageBundle\Service\SalaryCalculatorService;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;

// 获取薪资计算服务
$calculator = $container->get(SalaryCalculatorService::class);

// 创建员工和薪资期间
$employee = new Employee();
$employee->setEmployeeNumber('EMP001');
$employee->setName('张三');
$employee->setBaseSalary('8000.00');

$period = new PayrollPeriod();
$period->setStartDate(new DateTime('2024-01-01'));
$period->setEndDate(new DateTime('2024-01-31'));

// 计算薪资
$calculation = $calculator->calculate($employee, $period);

// 获取计算结果
$grossSalary = $calculation->getGrossSalary();      // 税前薪资
$netSalary = $calculation->getNetSalary();          // 税后薪资
$totalDeductions = $calculation->getTotalDeductions(); // 总扣款
```

### 税务计算

```php
use Tourze\SalaryManageBundle\Service\TaxCalculatorService;

$taxCalculator = $container->get(TaxCalculatorService::class);

$taxResult = $taxCalculator->calculateTax($employee, $grossSalary);

$incomeTax = $taxResult->getIncomeTax();              // 个人所得税
$socialInsuranceTax = $taxResult->getSocialInsuranceTax(); // 社保税务
$totalTax = $taxResult->getTotalTax();                // 总税额
```

### 社保计算

```php
use Tourze\SalaryManageBundle\Service\SocialInsuranceCalculatorService;

$insuranceCalculator = $container->get(SocialInsuranceCalculatorService::class);

$insuranceResult = $insuranceCalculator->calculate($employee, $grossSalary);

$pensionInsurance = $insuranceResult->getPensionInsurance();     // 养老保险
$medicalInsurance = $insuranceResult->getMedicalInsurance();     // 医疗保险
$unemploymentInsurance = $insuranceResult->getUnemploymentInsurance(); // 失业保险
```

### 报表生成

```php
use Tourze\SalaryManageBundle\Service\ReportGeneratorService;

$reportGenerator = $container->get(ReportGeneratorService::class);

// 生成月度薪资报表
$report = $reportGenerator->generateReport([
    'type' => 'monthly_salary',
    'period' => $period,
    'department' => 'IT'
]);

// 导出为 Excel
$excelFile = $reportGenerator->exportToExcel($report, 'salary_report.xlsx');
```

### 数据导入

```php
use Tourze\SalaryManageBundle\Service\DataImportExportService;

$dataService = $container->get(DataImportExportService::class);

// 从 CSV 导入员工数据
$result = $dataService->importEmployees('employees.csv', [
    'employee_number' => 0,  // 员工编号列
    'name' => 1,             // 姓名列
    'department' => 2,       // 部门列
    'base_salary' => 3       // 基本薪资列
]);
```

## 🔧 高级配置

Bundle 提供灵活的配置选项：

```yaml
# config/packages/salary_manage.yaml
salary_manage:
    # 地区配置
    regional:
        default_region: 'CN'
        regions:
            CN:
                currency: 'CNY'
                tax_year: 'calendar'
                social_insurance_rates:
                    pension: 0.08      # 养老保险 8%
                    medical: 0.02       # 医疗保险 2%
                    unemployment: 0.005 # 失业保险 0.5%

    # 计算规则
    calculation:
        overtime_rate: 1.5    # 加班费倍率
        weekend_rate: 2.0     # 周末加班倍率
        holiday_rate: 3.0     # 节假日加班倍率

    # 审批工作流
    approval:
        enabled: true
        required_approvers: 2       # 需要的审批人数
        auto_approve_threshold: 1000 # 自动审批阈值
```

## 🏗️ 核心实体

### 主要实体

- **Employee**：员工信息和薪资详情
- **PayrollPeriod**：薪资期间定义
- **SalaryCalculation**：薪资计算结果
- **SalaryItem**：个人薪资组成部分
- **TaxBracket**：税级配置
- **TaxResult**：税务计算结果
- **SocialInsuranceResult**：社保计算结果
- **Deduction**：各种扣款类型
- **PaymentRecord**：支付处理记录
- **ApprovalRequest**：审批工作流请求

### 枚举类型

- **SalaryItemType**：薪资项目类型（基本工资、加班费、奖金等）
- **DeductionType**：扣款类型（税收、保险等）
- **PaymentStatus**：支付处理状态
- **PaymentMethod**：支付方式（银行转账、现金等）
- **ApprovalStatus**：审批工作流状态
- **InsuranceType**：社保类型
- **ReportType**：可用报表类型

## 🛠️ 核心服务

### 主要服务

- **SalaryCalculatorService**：主要薪资计算引擎
- **TaxCalculatorService**：税务计算服务
- **SocialInsuranceCalculatorService**：社保计算
- **ReportGeneratorService**：报表生成和导出
- **ApprovalWorkflowService**：审批工作流管理
- **PaymentProcessorService**：支付处理
- **DataImportExportService**：数据导入导出
- **ExternalSystemService**：外部系统集成

### 接口设计

所有服务都实现相应接口，确保更好的可测试性和灵活性：

- `SalaryCalculatorInterface`
- `TaxCalculatorInterface`
- `SocialInsuranceCalculatorInterface`
- `ReportGeneratorInterface`
- `ApprovalWorkflowInterface`
- `PaymentProcessorInterface`
- `DataImportExportInterface`

## ⚠️ 异常处理

Bundle 提供特定的异常类型以便更好的错误处理：

- **SalaryCalculationException**：薪资计算错误
- **TaxCalculationException**：税务计算错误
- **InsuranceCalculationException**：社保计算错误
- **PaymentProcessingException**：支付处理错误
- **ApprovalWorkflowException**：审批工作流错误
- **DataValidationException**：数据验证错误
- **DataAccessException**：数据访问错误
- **ReportGeneratorException**：报表生成错误

## 🧪 测试

Bundle 包含全面的测试：

```bash
# 运行所有测试
php vendor/bin/phpunit

# 运行特定测试套件
php vendor/bin/phpunit tests/Service/
php vendor/bin/phpunit tests/Entity/
php vendor/bin/phpunit tests/Exception/
```

## 🔗 集成示例

### EasyAdmin 集成

Bundle 提供 EasyAdmin 集成以便快速搭建管理界面：

```php
// config/packages/easy_admin.yaml
easy_admin:
    entities:
        - Tourze\SalaryManageBundle\Entity\Employee
        - Tourze\SalaryManageBundle\Entity\SalaryCalculation
        - Tourze\SalaryManageBundle\Entity\PayrollPeriod
```

### 外部系统集成

```php
use Tourze\SalaryManageBundle\Interface\ExternalSystemInterface;

class CustomERPSystem implements ExternalSystemInterface
{
    public function syncEmployeeData(Employee $employee): bool
    {
        // 同步员工数据到外部 ERP 系统
        return true;
    }

    public function getAttendanceData(Employee $employee, \DateTime $startDate, \DateTime $endDate): array
    {
        // 从外部系统获取考勤数据
        return [];
    }
}
```

## 🚀 性能优化

- **缓存**：Bundle 使用 Symfony 缓存组件保存计算结果
- **批量处理**：支持批量薪资计算
- **懒加载**：实体使用懒加载优化性能
- **查询优化**：针对大数据集优化数据库查询

## 🔒 安全性

- **输入验证**：所有输入都使用 Symfony 验证器进行验证
- **数据加密**：敏感数据在数据库中加密存储
- **访问控制**：支持基于角色的访问控制
- **审计追踪**：所有操作都有完整的审计追踪

## 🤝 贡献

在提交 Pull Request 之前，请阅读我们的贡献指南。

## 📄 许可证

本 Bundle 采用 MIT 许可证。详情请参阅 LICENSE 文件。

## 📞 支持

获取支持和文档：

- 📖 [文档](docs/)
- 🐛 [问题追踪](https://github.com/tourze/salary-manage-bundle/issues)
- 💬 [讨论区](https://github.com/tourze/salary-manage-bundle/discussions)

## 📝 更新日志

查看 [CHANGELOG.md](CHANGELOG.md) 了解每个版本的详细变更。
