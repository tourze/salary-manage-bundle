<?php

namespace Tourze\SalaryManageBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 专项附加扣除类型枚举
 * 支持2025年个人所得税法规定的6项专项附加扣除
 */
enum DeductionType: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case ChildEducation = 'child_education';        // 子女教育
    case ContinuingEducation = 'continuing_education'; // 继续教育
    case SeriousIllness = 'serious_illness';        // 大病医疗
    case HousingLoan = 'housing_loan';              // 住房贷款利息
    case HousingRent = 'housing_rent';              // 住房租金
    case ElderCare = 'elder_care';                  // 赡养老人

    /**
     * 获取扣除类型的中文标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ChildEducation => '子女教育',
            self::ContinuingEducation => '继续教育',
            self::SeriousIllness => '大病医疗',
            self::HousingLoan => '住房贷款利息',
            self::HousingRent => '住房租金',
            self::ElderCare => '赡养老人',
        };
    }

    /**
     * 获取扣除类型的最大月度限额
     */
    public function getMonthlyLimit(): float
    {
        return match ($this) {
            self::ChildEducation => 2000,    // 每月2000元
            self::ContinuingEducation => 400, // 每月400元
            self::SeriousIllness => 6666.67, // 年度80000元/12月
            self::HousingLoan => 1000,       // 每月1000元
            self::HousingRent => 1500,       // 每月最高1500元
            self::ElderCare => 3000,         // 每月最高3000元
        };
    }
}
