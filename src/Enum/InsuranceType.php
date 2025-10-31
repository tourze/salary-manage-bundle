<?php

namespace Tourze\SalaryManageBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 社保类型枚举
 * 支持中国五险一金完整体系
 */
enum InsuranceType: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case Pension = 'pension';                    // 养老保险
    case Medical = 'medical';                    // 医疗保险
    case Unemployment = 'unemployment';          // 失业保险
    case WorkInjury = 'work_injury';            // 工伤保险
    case Maternity = 'maternity';               // 生育保险
    case HousingFund = 'housing_fund';          // 住房公积金

    /**
     * 获取保险类型的中文名称
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Pension => '养老保险',
            self::Medical => '医疗保险',
            self::Unemployment => '失业保险',
            self::WorkInjury => '工伤保险',
            self::Maternity => '生育保险',
            self::HousingFund => '住房公积金',
        };
    }

    /**
     * 获取标准缴费比例（企业缴费比例）
     * 注意：各地区可能有差异，这里是全国通用标准
     */
    public function getStandardEmployerRate(): float
    {
        return match ($this) {
            self::Pension => 0.20,      // 养老保险：企业20%
            self::Medical => 0.08,      // 医疗保险：企业8%
            self::Unemployment => 0.007, // 失业保险：企业0.7%
            self::WorkInjury => 0.005,  // 工伤保险：企业0.5%
            self::Maternity => 0.008,   // 生育保险：企业0.8%
            self::HousingFund => 0.12,  // 住房公积金：企业12%
        };
    }

    /**
     * 获取标准缴费比例（个人缴费比例）
     */
    public function getStandardEmployeeRate(): float
    {
        return match ($this) {
            self::Pension => 0.08,      // 养老保险：个人8%
            self::Medical => 0.02,      // 医疗保险：个人2%
            self::Unemployment => 0.003, // 失业保险：个人0.3%
            self::WorkInjury => 0.0,    // 工伤保险：个人不缴费
            self::Maternity => 0.0,     // 生育保险：个人不缴费
            self::HousingFund => 0.12,  // 住房公积金：个人12%
        };
    }

    /**
     * 是否属于五险（社会保险）
     */
    public function isSocialInsurance(): bool
    {
        return self::HousingFund !== $this;
    }

    /**
     * 是否属于税前扣除项目
     */
    public function isTaxDeductible(): bool
    {
        return true; // 五险一金均为税前扣除
    }
}
