<?php

namespace Tourze\SalaryManageBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ApprovalStatus: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case Pending = 'pending';       // 待审批
    case Approved = 'approved';     // 已审批
    case Rejected = 'rejected';     // 已拒绝
    case Withdrawn = 'withdrawn';   // 已撤回

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待审批',
            self::Approved => '已审批',
            self::Rejected => '已拒绝',
            self::Withdrawn => '已撤回',
        };
    }

    public function isCompleted(): bool
    {
        return match ($this) {
            self::Approved, self::Rejected, self::Withdrawn => true,
            default => false,
        };
    }

    public function canWithdraw(): bool
    {
        return self::Pending === $this;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'orange',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Withdrawn => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => 'clock',
            self::Approved => 'check',
            self::Rejected => 'times',
            self::Withdrawn => 'undo',
        };
    }

    /**
     * 返回枚举的字符串值，用于解决EasyAdmin字符串转换问题
     * 在EasyAdmin表单中，应当使用: $enum->getValue() 而不是 (string)$enum
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
