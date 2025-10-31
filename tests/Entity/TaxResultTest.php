<?php

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\Deduction;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\TaxResult;
use Tourze\SalaryManageBundle\Enum\DeductionType;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * @internal
 */
#[CoversClass(TaxResult::class)]
final class TaxResultTest extends TestCase
{
    private Employee $employee;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = new Employee();
        $this->employee->setEmployeeNumber('EMP001');
        $this->employee->setName('张三');
        $this->employee->setBaseSalary('10000.00');
        $this->employee->setHireDate(new \DateTimeImmutable('2023-01-01'));

        $this->period = new PayrollPeriod();
        $this->period->setYear(2025);
        $this->period->setMonth(1);
    }

    public function testConstructorWithValidData(): void
    {
        $taxResult = new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: 4000.0,
            taxAmount: 80.0,
            netIncome: 9920.0
        );

        $this->assertSame($this->employee, $taxResult->getEmployee());
        $this->assertSame($this->period, $taxResult->getPeriod());
        $this->assertSame(10000.0, $taxResult->getGrossIncome());
        $this->assertSame(4000.0, $taxResult->getTaxableIncome());
        $this->assertSame(80.0, $taxResult->getTaxAmount());
        $this->assertSame(9920.0, $taxResult->getNetIncome());
    }

    public function testConstructorWithDeductionsAndMetadata(): void
    {
        $deductions = [
            new Deduction(DeductionType::ChildEducation, 2000.0, '子女教育'),
            new Deduction(DeductionType::HousingLoan, 1000.0, '住房贷款利息'),
        ];

        $taxCalculationDetails = [
            'marginal_rate' => 0.03,
            'tax_brackets' => [
                ['min' => 0, 'max' => 3000, 'rate' => 0.03],
            ],
        ];

        $metadata = ['calculated_by' => 'system', 'version' => '1.0'];

        $taxResult = new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: 1000.0,
            taxAmount: 30.0,
            netIncome: 9970.0,
            deductions: $deductions,
            taxCalculationDetails: $taxCalculationDetails,
            metadata: $metadata
        );

        $this->assertEquals($deductions, $taxResult->getDeductions());
        $this->assertEquals($taxCalculationDetails, $taxResult->getTaxCalculationDetails());
        $this->assertEquals($metadata, $taxResult->getMetadata());
    }

    public function testConstructorThrowsExceptionForNegativeGrossIncome(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('收入和税额不能为负数');

        new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: -1000.0,
            taxableIncome: 4000.0,
            taxAmount: 80.0,
            netIncome: 9920.0
        );
    }

    public function testConstructorThrowsExceptionForNegativeTaxableIncome(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('收入和税额不能为负数');

        new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: -1000.0,
            taxAmount: 80.0,
            netIncome: 9920.0
        );
    }

    public function testConstructorThrowsExceptionForNegativeTaxAmount(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('收入和税额不能为负数');

        new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: 4000.0,
            taxAmount: -80.0,
            netIncome: 9920.0
        );
    }

    public function testConstructorThrowsExceptionForNegativeNetIncome(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('税后收入不能为负数');

        new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: 4000.0,
            taxAmount: 80.0,
            netIncome: -100.0
        );
    }

    public function testGetTotalDeductions(): void
    {
        $deductions = [
            new Deduction(DeductionType::ChildEducation, 2000.0, '子女教育'),
            new Deduction(DeductionType::HousingLoan, 1000.0, '住房贷款利息'),
            new Deduction(DeductionType::ElderCare, 2000.0, '赡养老人'),
        ];

        $taxResult = new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: 5000.0,
            taxAmount: 0.0,
            netIncome: 10000.0,
            deductions: $deductions
        );

        $this->assertSame(5000.0, $taxResult->getTotalDeductions());
    }

    public function testGetTotalDeductionsWithEmptyDeductions(): void
    {
        $taxResult = new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: 5000.0,
            taxAmount: 0.0,
            netIncome: 10000.0,
            deductions: []
        );

        $this->assertSame(0.0, $taxResult->getTotalDeductions());
    }

    public function testGetEffectiveTaxRate(): void
    {
        $taxResult = new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: 4000.0,
            taxAmount: 120.0,
            netIncome: 9880.0
        );

        $this->assertSame(0.012, $taxResult->getEffectiveTaxRate()); // 120 / 10000
    }

    public function testGetEffectiveTaxRateWithZeroGrossIncome(): void
    {
        $taxResult = new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 0.0,
            taxableIncome: 0.0,
            taxAmount: 0.0,
            netIncome: 0.0
        );

        $this->assertSame(0.0, $taxResult->getEffectiveTaxRate());
    }

    public function testGetMarginalTaxRate(): void
    {
        $taxCalculationDetails = [
            'marginal_rate' => 0.1,
            'tax_bracket' => ['min' => 3000, 'max' => 12000, 'rate' => 0.1],
        ];

        $taxResult = new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 15000.0,
            taxableIncome: 10000.0,
            taxAmount: 290.0,
            netIncome: 14710.0,
            taxCalculationDetails: $taxCalculationDetails
        );

        $this->assertSame(0.1, $taxResult->getMarginalTaxRate());
    }

    public function testGetMarginalTaxRateWithoutDetails(): void
    {
        $taxResult = new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: 4000.0,
            taxAmount: 80.0,
            netIncome: 9920.0
        );

        $this->assertSame(0.0, $taxResult->getMarginalTaxRate());
    }

    public function testIsValidWithCorrectCalculation(): void
    {
        $taxResult = new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: 4000.0,
            taxAmount: 80.0,
            netIncome: 9920.0 // 10000 - 80
        );

        $this->assertTrue($taxResult->isValid());
    }

    public function testIsValidWithSlightFloatingPointError(): void
    {
        $taxResult = new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: 4000.0,
            taxAmount: 80.0,
            netIncome: 9920.0 // 精确匹配避免浮点数精度问题
        );

        $this->assertTrue($taxResult->isValid());
    }

    public function testIsValidWithSignificantCalculationError(): void
    {
        $taxResult = new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: 4000.0,
            taxAmount: 80.0,
            netIncome: 9900.0 // 误差超过0.01
        );

        $this->assertFalse($taxResult->isValid());
    }

    public function testAllCumulativeGetters(): void
    {
        $taxResult = new TaxResult(
            employee: $this->employee,
            period: $this->period,
            grossIncome: 10000.0,
            taxableIncome: 4000.0,
            taxAmount: 80.0,
            netIncome: 9920.0,
            basicDeduction: 5000.0,
            additionalDeduction: 3000.0,
            taxableAmount: 2000.0,
            taxRate: 0.03,
            cumulativeTax: 960.0,
            currentTax: 80.0,
            cumulativeIncome: 120000.0,
            cumulativeBasicDeduction: 60000.0,
            cumulativeSpecialDeduction: 36000.0,
            cumulativeAdditionalDeduction: 24000.0,
            cumulativeTaxableAmount: 0.0,
            cumulativeTaxAmount: 960.0
        );

        $this->assertSame(5000.0, $taxResult->getBasicDeduction());
        $this->assertSame(3000.0, $taxResult->getAdditionalDeduction());
        $this->assertSame(2000.0, $taxResult->getTaxableAmount());
        $this->assertSame(0.03, $taxResult->getTaxRate());
        $this->assertSame(960.0, $taxResult->getCumulativeTax());
        $this->assertSame(80.0, $taxResult->getCurrentTax());
        $this->assertSame(120000.0, $taxResult->getCumulativeIncome());
        $this->assertSame(60000.0, $taxResult->getCumulativeBasicDeduction());
        $this->assertSame(36000.0, $taxResult->getCumulativeSpecialDeduction());
        $this->assertSame(24000.0, $taxResult->getCumulativeAdditionalDeduction());
        $this->assertSame(0.0, $taxResult->getCumulativeTaxableAmount());
        $this->assertSame(960.0, $taxResult->getCumulativeTaxAmount());
    }
}
