<?php

namespace Tourze\SalaryManageBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum PaymentStatus: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case Pending = 'pending';           // 待处理
    case Processing = 'processing';     // 处理中
    case Success = 'success';           // 成功
    case Failed = 'failed';             // 失败
    case Cancelled = 'cancelled';       // 已取消
    case Refunded = 'refunded';         // 已退款

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待处理',
            self::Processing => '处理中',
            self::Success => '成功',
            self::Failed => '失败',
            self::Cancelled => '已取消',
            self::Refunded => '已退款',
        };
    }

    public function isCompleted(): bool
    {
        return match ($this) {
            self::Success, self::Failed, self::Cancelled, self::Refunded => true,
            default => false,
        };
    }

    public function canCancel(): bool
    {
        return match ($this) {
            self::Pending, self::Processing => true,
            default => false,
        };
    }

    public function canRetry(): bool
    {
        return self::Failed === $this;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'orange',
            self::Processing => 'blue',
            self::Success => 'green',
            self::Failed => 'red',
            self::Cancelled => 'gray',
            self::Refunded => 'purple',
        };
    }
}
