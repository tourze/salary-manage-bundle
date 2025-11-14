# Salary Manage Bundle

[中文](README.zh-CN.md) | [English](README.md)

A comprehensive enterprise salary management Symfony Bundle that provides complete solutions for salary calculation, tax processing, and social insurance management.

## Features

- **💰 Salary Calculation**: Flexible salary calculation engine with configurable rules and support for various pay components
- **🧾 Tax Management**: Automated tax calculation with support for multiple tax brackets and regional tax policies
- **🛡️ Social Insurance**: Comprehensive social insurance calculation including pension, medical, unemployment, work injury, and maternity insurance
- **📊 Reporting**: Advanced reporting system with multiple report types and export capabilities
- **🔄 Workflow**: Approval workflow system for salary calculations and payments
- **💳 Payment Processing**: Integrated payment processing with multiple payment methods and status tracking
- **📁 Data Import/Export**: Flexible data import and export functionality for integration with external systems
- **🎯 Performance Integration**: Performance data integration for performance-based salary calculations
- **🕒 Attendance Integration**: Attendance data integration for attendance-based salary calculations

## Requirements

- PHP ^8.2
- Symfony ^7.3
- Doctrine ORM ^3.0

## Installation

```bash
composer require tourze/salary-manage-bundle
```

## Configuration

Enable the bundle in your Symfony application:

```php
// config/bundles.php
return [
    // ...
    Tourze\SalaryManageBundle\SalaryManageBundle::class => ['all' => true],
];
```

## Basic Usage

### Salary Calculation

```php
use Tourze\SalaryManageBundle\Service\SalaryCalculatorService;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;

// Get the salary calculator service
$calculator = $container->get(SalaryCalculatorService::class);

// Create employee and payroll period
$employee = new Employee();
$employee->setEmployeeNumber('EMP001');
$employee->setName('John Doe');
$employee->setBaseSalary('5000.00');

$period = new PayrollPeriod();
$period->setStartDate(new DateTime('2024-01-01'));
$period->setEndDate(new DateTime('2024-01-31'));

// Calculate salary
$calculation = $calculator->calculate($employee, $period);

// Get calculation results
$grossSalary = $calculation->getGrossSalary();
$netSalary = $calculation->getNetSalary();
$totalDeductions = $calculation->getTotalDeductions();
```

### Tax Calculation

```php
use Tourze\SalaryManageBundle\Service\TaxCalculatorService;

$taxCalculator = $container->get(TaxCalculatorService::class);

$taxResult = $taxCalculator->calculateTax($employee, $grossSalary);

$incomeTax = $taxResult->getIncomeTax();
$socialInsuranceTax = $taxResult->getSocialInsuranceTax();
$totalTax = $taxResult->getTotalTax();
```

### Social Insurance Calculation

```php
use Tourze\SalaryManageBundle\Service\SocialInsuranceCalculatorService;

$insuranceCalculator = $container->get(SocialInsuranceCalculatorService::class);

$insuranceResult = $insuranceCalculator->calculate($employee, $grossSalary);

$pensionInsurance = $insuranceResult->getPensionInsurance();
$medicalInsurance = $insuranceResult->getMedicalInsurance();
$unemploymentInsurance = $insuranceResult->getUnemploymentInsurance();
```

### Report Generation

```php
use Tourze\SalaryManageBundle\Service\ReportGeneratorService;

$reportGenerator = $container->get(ReportGeneratorService::class);

// Generate monthly salary report
$report = $reportGenerator->generateReport([
    'type' => 'monthly_salary',
    'period' => $period,
    'department' => 'IT'
]);

// Export to Excel
$excelFile = $reportGenerator->exportToExcel($report, 'salary_report.xlsx');
```

### Data Import

```php
use Tourze\SalaryManageBundle\Service\DataImportExportService;

$dataService = $container->get(DataImportExportService::class);

// Import employee data from CSV
$result = $dataService->importEmployees('employees.csv', [
    'employee_number' => 0,
    'name' => 1,
    'department' => 2,
    'base_salary' => 3
]);
```

## Configuration

The bundle provides flexible configuration options:

```yaml
# config/packages/salary_manage.yaml
salary_manage:
    # Regional configuration
    regional:
        default_region: 'CN'
        regions:
            CN:
                currency: 'CNY'
                tax_year: 'calendar'
                social_insurance_rates:
                    pension: 0.08
                    medical: 0.02
                    unemployment: 0.005

    # Calculation rules
    calculation:
        overtime_rate: 1.5
        weekend_rate: 2.0
        holiday_rate: 3.0

    # Approval workflow
    approval:
        enabled: true
        required_approvers: 2
        auto_approve_threshold: 1000
```

## Entities

### Core Entities

- **Employee**: Employee information and salary details
- **PayrollPeriod**: Payroll period definition
- **SalaryCalculation**: Salary calculation results
- **SalaryItem**: Individual salary components
- **TaxBracket**: Tax bracket configuration
- **TaxResult**: Tax calculation results
- **SocialInsuranceResult**: Social insurance calculation results
- **Deduction**: Various deduction types
- **PaymentRecord**: Payment processing records
- **ApprovalRequest**: Approval workflow requests

### Enums

- **SalaryItemType**: Types of salary items (base, overtime, bonus, etc.)
- **DeductionType**: Types of deductions (tax, insurance, etc.)
- **PaymentStatus**: Payment processing status
- **PaymentMethod**: Payment methods (bank transfer, cash, etc.)
- **ApprovalStatus**: Approval workflow status
- **InsuranceType**: Social insurance types
- **ReportType**: Available report types

## Services

### Core Services

- **SalaryCalculatorService**: Main salary calculation engine
- **TaxCalculatorService**: Tax calculation service
- **SocialInsuranceCalculatorService**: Social insurance calculation
- **ReportGeneratorService**: Report generation and export
- **ApprovalWorkflowService**: Approval workflow management
- **PaymentProcessorService**: Payment processing
- **DataImportExportService**: Data import and export
- **ExternalSystemService**: External system integration

### Interfaces

All services implement corresponding interfaces for better testability and flexibility:

- `SalaryCalculatorInterface`
- `TaxCalculatorInterface`
- `SocialInsuranceCalculatorInterface`
- `ReportGeneratorInterface`
- `ApprovalWorkflowInterface`
- `PaymentProcessorInterface`
- `DataImportExportInterface`

## Exception Handling

The bundle provides specific exception types for better error handling:

- **SalaryCalculationException**: Salary calculation errors
- **TaxCalculationException**: Tax calculation errors
- **InsuranceCalculationException**: Social insurance calculation errors
- **PaymentProcessingException**: Payment processing errors
- **ApprovalWorkflowException**: Approval workflow errors
- **DataValidationException**: Data validation errors
- **DataAccessException**: Data access errors
- **ReportGeneratorException**: Report generation errors

## Testing

The bundle includes comprehensive tests:

```bash
# Run all tests
php vendor/bin/phpunit

# Run specific test suites
php vendor/bin/phpunit tests/Service/
php vendor/bin/phpunit tests/Entity/
php vendor/bin/phpunit tests/Exception/
```

## Integration Examples

### EasyAdmin Integration

The bundle provides EasyAdmin integration for quick admin interface setup:

```php
// config/packages/easy_admin.yaml
easy_admin:
    entities:
        - Tourze\SalaryManageBundle\Entity\Employee
        - Tourze\SalaryManageBundle\Entity\SalaryCalculation
        - Tourze\SalaryManageBundle\Entity\PayrollPeriod
```

### External System Integration

```php
use Tourze\SalaryManageBundle\Interface\ExternalSystemInterface;

class CustomERPSystem implements ExternalSystemInterface
{
    public function syncEmployeeData(Employee $employee): bool
    {
        // Sync employee data to external ERP system
        return true;
    }

    public function getAttendanceData(Employee $employee, \DateTime $startDate, \DateTime $endDate): array
    {
        // Get attendance data from external system
        return [];
    }
}
```

## Performance Considerations

- **Caching**: The bundle uses Symfony cache component for calculation results
- **Batch Processing**: Support for batch salary calculations
- **Lazy Loading**: Entities use lazy loading for optimal performance
- **Query Optimization**: Optimized database queries for large datasets

## Security

- **Input Validation**: All inputs are validated using Symfony validator
- **Data Encryption**: Sensitive data is encrypted in database
- **Access Control**: Support for role-based access control
- **Audit Trail**: Complete audit trail for all operations

## Contributing

Please read our contributing guidelines before submitting pull requests.

## License

This bundle is licensed under the MIT License. See the LICENSE file for details.

## Support

For support and documentation:

- 📚 [Documentation](docs/)
- 🐛 [Issue Tracker](https://github.com/tourze/salary-manage-bundle/issues)
- 💬 [Discussions](https://github.com/tourze/salary-manage-bundle/discussions)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for details about changes in each version.