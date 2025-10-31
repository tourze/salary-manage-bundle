<?php

namespace Tourze\SalaryManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SalaryManageBundle\Enum\ReportType;

/**
 * @internal
 */
#[CoversClass(ReportType::class)]
class ReportTypeTest extends AbstractEnumTestCase
{
    public function testAllReportTypesHaveCorrectValues(): void
    {
        $this->assertEquals('payroll_summary', ReportType::PayrollSummary->value);
        $this->assertEquals('tax_report', ReportType::TaxReport->value);
        $this->assertEquals('social_insurance_report', ReportType::SocialInsuranceReport->value);
        $this->assertEquals('individual_tax_report', ReportType::IndividualTaxReport->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('薪资发放汇总报告', ReportType::PayrollSummary->getLabel());
        $this->assertEquals('个税申报报告', ReportType::TaxReport->getLabel());
        $this->assertEquals('社保缴费汇总报告', ReportType::SocialInsuranceReport->getLabel());
        $this->assertEquals('个人所得税报告', ReportType::IndividualTaxReport->getLabel());
    }

    public function testGetDescription(): void
    {
        $this->assertStringContainsString('统计指定期间内所有员工的薪资发放情况', ReportType::PayrollSummary->getDescription());
        $this->assertStringContainsString('生成符合税务机关要求的个税申报文件', ReportType::TaxReport->getDescription());
        $this->assertStringContainsString('统计社保和公积金缴费情况', ReportType::SocialInsuranceReport->getDescription());
        $this->assertStringContainsString('员工个人所得税计算明细报告', ReportType::IndividualTaxReport->getDescription());
    }

    public function testGetRequiredFields(): void
    {
        $payrollFields = ReportType::PayrollSummary->getRequiredFields();
        $this->assertContains('employee_name', $payrollFields);
        $this->assertContains('employee_number', $payrollFields);
        $this->assertContains('gross_amount', $payrollFields);
        $this->assertContains('net_amount', $payrollFields);

        $taxFields = ReportType::TaxReport->getRequiredFields();
        $this->assertContains('employee_name', $taxFields);
        $this->assertContains('taxable_income', $taxFields);
        $this->assertContains('tax_amount', $taxFields);

        $socialFields = ReportType::SocialInsuranceReport->getRequiredFields();
        $this->assertContains('contribution_base', $socialFields);
        $this->assertContains('total_contribution', $socialFields);

        $individualFields = ReportType::IndividualTaxReport->getRequiredFields();
        $this->assertContains('monthly_income', $individualFields);
        $this->assertContains('cumulative_tax', $individualFields);
        $this->assertContains('current_tax', $individualFields);
    }

    public function testEnumImplementsRequiredInterfaces(): void
    {
        $reflection = new \ReflectionEnum(ReportType::class);
        $interfaces = $reflection->getInterfaceNames();

        $this->assertContains('Tourze\EnumExtra\Itemable', $interfaces);
        $this->assertContains('Tourze\EnumExtra\Labelable', $interfaces);
        $this->assertContains('Tourze\EnumExtra\Selectable', $interfaces);
    }

    public function testEnumUsesRequiredTraits(): void
    {
        $reflection = new \ReflectionEnum(ReportType::class);
        $traits = $reflection->getTraitNames();

        $this->assertContains('Tourze\EnumExtra\ItemTrait', $traits);
        $this->assertContains('Tourze\EnumExtra\SelectTrait', $traits);
    }

    public function testAllCasesHaveLabels(): void
    {
        foreach (ReportType::cases() as $case) {
            $label = $case->getLabel();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
            $this->assertStringContainsString('报告', $label);
        }
    }

    public function testAllCasesHaveDescriptions(): void
    {
        foreach (ReportType::cases() as $case) {
            $description = $case->getDescription();
            $this->assertIsString($description);
            $this->assertNotEmpty($description);
            $this->assertGreaterThan(10, strlen($description));
        }
    }

    public function testAllCasesHaveRequiredFields(): void
    {
        foreach (ReportType::cases() as $case) {
            $fields = $case->getRequiredFields();
            $this->assertIsArray($fields);
            $this->assertNotEmpty($fields);
            $this->assertContains('employee_name', $fields);
            $this->assertContains('employee_number', $fields);
        }
    }

    public function testRequiredFieldsAreConsistent(): void
    {
        foreach (ReportType::cases() as $case) {
            $fields = $case->getRequiredFields();
            foreach ($fields as $field) {
                $this->assertIsString($field);
                $this->assertNotEmpty($field);
                $this->assertMatchesRegularExpression('/^[a-z_]+$/', $field);
            }
        }
    }

    public function testToArray(): void
    {
        foreach (ReportType::cases() as $case) {
            $array = $case->toArray();

            $this->assertIsArray($array);
            $this->assertArrayHasKey('value', $array);
            $this->assertArrayHasKey('label', $array);
            $this->assertEquals($case->value, $array['value']);
            $this->assertEquals($case->getLabel(), $array['label']);
        }
    }
}
