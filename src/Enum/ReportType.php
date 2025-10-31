<?php

namespace Tourze\SalaryManageBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ReportType: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case PayrollSummary = 'payroll_summary';
    case TaxReport = 'tax_report';
    case SocialInsuranceReport = 'social_insurance_report';
    case IndividualTaxReport = 'individual_tax_report';

    public function getLabel(): string
    {
        return match ($this) {
            self::PayrollSummary => '薪资发放汇总报告',
            self::TaxReport => '个税申报报告',
            self::SocialInsuranceReport => '社保缴费汇总报告',
            self::IndividualTaxReport => '个人所得税报告',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::PayrollSummary => '统计指定期间内所有员工的薪资发放情况，包括应发、实发、扣除等明细',
            self::TaxReport => '生成符合税务机关要求的个税申报文件和汇总数据',
            self::SocialInsuranceReport => '统计社保和公积金缴费情况，支持按险种分类汇总',
            self::IndividualTaxReport => '员工个人所得税计算明细报告，包括各项扣除和税额计算过程',
        };
    }

    /** @return array<int, string> */
    public function getRequiredFields(): array
    {
        return match ($this) {
            self::PayrollSummary => ['employee_name', 'employee_number', 'gross_amount', 'net_amount', 'deductions'],
            self::TaxReport => ['employee_name', 'employee_number', 'taxable_income', 'tax_amount', 'deductions'],
            self::SocialInsuranceReport => ['employee_name', 'employee_number', 'contribution_base', 'total_contribution'],
            self::IndividualTaxReport => ['employee_name', 'employee_number', 'monthly_income', 'cumulative_tax', 'current_tax'],
        };
    }
}
