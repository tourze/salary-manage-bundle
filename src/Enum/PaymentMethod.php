<?php

namespace Tourze\SalaryManageBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum PaymentMethod: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case BankTransfer = 'bank_transfer';        // 银行转账
    case Cash = 'cash';                         // 现金发放
    case Check = 'check';                       // 支票发放
    case DigitalWallet = 'digital_wallet';      // 数字钱包
    case Payroll = 'payroll';                   // 代发工资

    public function getLabel(): string
    {
        return match ($this) {
            self::BankTransfer => '银行转账',
            self::Cash => '现金发放',
            self::Check => '支票发放',
            self::DigitalWallet => '数字钱包',
            self::Payroll => '代发工资',
        };
    }

    public function requiresBankInfo(): bool
    {
        return match ($this) {
            self::BankTransfer, self::Payroll => true,
            default => false,
        };
    }

    public function isAutomated(): bool
    {
        return match ($this) {
            self::BankTransfer, self::DigitalWallet, self::Payroll => true,
            default => false,
        };
    }

    public function getProcessingTime(): string
    {
        return match ($this) {
            self::BankTransfer => '1-3个工作日',
            self::Cash => '即时',
            self::Check => '3-5个工作日',
            self::DigitalWallet => '即时',
            self::Payroll => '1-2个工作日',
        };
    }
}
