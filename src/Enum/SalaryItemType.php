<?php

namespace Tourze\SalaryManageBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 薪资项目类型枚举 (映射需求R1.2)
 * 支持至少10种薪资项目类型
 */
enum SalaryItemType: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
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
    case SocialInsurance = 'social_insurance';
    case IncomeTax = 'income_tax';

    public function getLabel(): string
    {
        return $this->getDisplayName();
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::BasicSalary => '基本工资',
            self::PerformanceBonus => '绩效工资',
            self::Bonus => '奖金',
            self::Allowance => '津贴',
            self::Subsidy => '补贴',
            self::Overtime => '加班费',
            self::Commission => '提成',
            self::SpecialReward => '专项奖励',
            self::TransportAllowance => '交通补助',
            self::MealAllowance => '餐饮补助',
            self::SocialInsurance => '社会保险',
            self::IncomeTax => '个人所得税',
        };
    }

    public function isDeduction(): bool
    {
        return match ($this) {
            self::SocialInsurance, self::IncomeTax => true,
            default => false,
        };
    }
}
