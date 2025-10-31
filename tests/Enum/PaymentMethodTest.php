<?php

namespace Tourze\SalaryManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SalaryManageBundle\Enum\PaymentMethod;

/**
 * @internal
 */
#[CoversClass(PaymentMethod::class)]
class PaymentMethodTest extends AbstractEnumTestCase
{
    public function testAllPaymentMethods(): void
    {
        $expectedMethods = [
            'bank_transfer',
            'cash',
            'check',
            'digital_wallet',
            'payroll',
        ];

        $actualMethods = array_map(fn (PaymentMethod $method) => $method->value, PaymentMethod::cases());

        $this->assertCount(5, PaymentMethod::cases());
        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetLabelForBankTransfer(): void
    {
        $this->assertEquals('银行转账', PaymentMethod::BankTransfer->getLabel());
    }

    public function testGetLabelForCash(): void
    {
        $this->assertEquals('现金发放', PaymentMethod::Cash->getLabel());
    }

    public function testGetLabelForCheck(): void
    {
        $this->assertEquals('支票发放', PaymentMethod::Check->getLabel());
    }

    public function testGetLabelForDigitalWallet(): void
    {
        $this->assertEquals('数字钱包', PaymentMethod::DigitalWallet->getLabel());
    }

    public function testGetLabelForPayroll(): void
    {
        $this->assertEquals('代发工资', PaymentMethod::Payroll->getLabel());
    }

    public function testRequiresBankInfoForBankTransfer(): void
    {
        $this->assertTrue(PaymentMethod::BankTransfer->requiresBankInfo());
    }

    public function testRequiresBankInfoForPayroll(): void
    {
        $this->assertTrue(PaymentMethod::Payroll->requiresBankInfo());
    }

    public function testRequiresBankInfoForCash(): void
    {
        $this->assertFalse(PaymentMethod::Cash->requiresBankInfo());
    }

    public function testRequiresBankInfoForCheck(): void
    {
        $this->assertFalse(PaymentMethod::Check->requiresBankInfo());
    }

    public function testRequiresBankInfoForDigitalWallet(): void
    {
        $this->assertFalse(PaymentMethod::DigitalWallet->requiresBankInfo());
    }

    public function testIsAutomatedForBankTransfer(): void
    {
        $this->assertTrue(PaymentMethod::BankTransfer->isAutomated());
    }

    public function testIsAutomatedForDigitalWallet(): void
    {
        $this->assertTrue(PaymentMethod::DigitalWallet->isAutomated());
    }

    public function testIsAutomatedForPayroll(): void
    {
        $this->assertTrue(PaymentMethod::Payroll->isAutomated());
    }

    public function testIsAutomatedForCash(): void
    {
        $this->assertFalse(PaymentMethod::Cash->isAutomated());
    }

    public function testIsAutomatedForCheck(): void
    {
        $this->assertFalse(PaymentMethod::Check->isAutomated());
    }

    public function testGetProcessingTimeForBankTransfer(): void
    {
        $this->assertEquals('1-3个工作日', PaymentMethod::BankTransfer->getProcessingTime());
    }

    public function testGetProcessingTimeForCash(): void
    {
        $this->assertEquals('即时', PaymentMethod::Cash->getProcessingTime());
    }

    public function testGetProcessingTimeForCheck(): void
    {
        $this->assertEquals('3-5个工作日', PaymentMethod::Check->getProcessingTime());
    }

    public function testGetProcessingTimeForDigitalWallet(): void
    {
        $this->assertEquals('即时', PaymentMethod::DigitalWallet->getProcessingTime());
    }

    public function testGetProcessingTimeForPayroll(): void
    {
        $this->assertEquals('1-2个工作日', PaymentMethod::Payroll->getProcessingTime());
    }

    public function testEnumValues(): void
    {
        $this->assertEquals('bank_transfer', PaymentMethod::BankTransfer->value);
        $this->assertEquals('cash', PaymentMethod::Cash->value);
        $this->assertEquals('check', PaymentMethod::Check->value);
        $this->assertEquals('digital_wallet', PaymentMethod::DigitalWallet->value);
        $this->assertEquals('payroll', PaymentMethod::Payroll->value);
    }

    public function testImplementsItemable(): void
    {
        $this->assertContains('Tourze\EnumExtra\Itemable', class_implements(PaymentMethod::class));
    }

    public function testImplementsLabelable(): void
    {
        $this->assertContains('Tourze\EnumExtra\Labelable', class_implements(PaymentMethod::class));
    }

    public function testImplementsSelectable(): void
    {
        $this->assertContains('Tourze\EnumExtra\Selectable', class_implements(PaymentMethod::class));
    }

    public function testUsesItemTrait(): void
    {
        $this->assertContains('Tourze\EnumExtra\ItemTrait', class_uses(PaymentMethod::class));
    }

    public function testUsesSelectTrait(): void
    {
        $this->assertContains('Tourze\EnumExtra\SelectTrait', class_uses(PaymentMethod::class));
    }

    public function testFromMethodWithValidValue(): void
    {
        $this->assertEquals(PaymentMethod::BankTransfer, PaymentMethod::from('bank_transfer'));
        $this->assertEquals(PaymentMethod::Cash, PaymentMethod::from('cash'));
        $this->assertEquals(PaymentMethod::Check, PaymentMethod::from('check'));
        $this->assertEquals(PaymentMethod::DigitalWallet, PaymentMethod::from('digital_wallet'));
        $this->assertEquals(PaymentMethod::Payroll, PaymentMethod::from('payroll'));
    }

    public function testTryFromMethodWithValidValue(): void
    {
        $this->assertEquals(PaymentMethod::BankTransfer, PaymentMethod::tryFrom('bank_transfer'));
        $this->assertEquals(PaymentMethod::Cash, PaymentMethod::tryFrom('cash'));
        $this->assertNull(PaymentMethod::tryFrom('invalid_method'));
    }

    public function testBankInfoAndAutomatedCorrelation(): void
    {
        // 需要银行信息的方法通常也是自动化的
        foreach (PaymentMethod::cases() as $method) {
            if ($method->requiresBankInfo()) {
                // 银行转账和代发工资都应该是自动化的
                $this->assertTrue($method->isAutomated(), "Method {$method->value} requires bank info so it should be automated");
            }
        }
    }

    public function testProcessingTimeConsistency(): void
    {
        foreach (PaymentMethod::cases() as $method) {
            $processingTime = $method->getProcessingTime();
            $this->assertIsString($processingTime);
            $this->assertNotEmpty($processingTime);

            // 自动化方法通常处理时间更快或明确
            if ($method->isAutomated()) {
                $this->assertNotEquals('', $processingTime);
            }
        }
    }

    public function testInstantMethods(): void
    {
        $instantMethods = [];
        foreach (PaymentMethod::cases() as $method) {
            if ('即时' === $method->getProcessingTime()) {
                $instantMethods[] = $method;
            }
        }

        $this->assertContains(PaymentMethod::Cash, $instantMethods);
        $this->assertContains(PaymentMethod::DigitalWallet, $instantMethods);
    }

    public function testMethodCharacteristics(): void
    {
        foreach (PaymentMethod::cases() as $method) {
            // 验证每个方法都有明确的特征
            $this->assertIsString($method->getLabel());
            $this->assertIsBool($method->requiresBankInfo());
            $this->assertIsBool($method->isAutomated());
            $this->assertIsString($method->getProcessingTime());

            // 验证标签不为空
            $this->assertNotEmpty($method->getLabel());
        }
    }

    public function testToArray(): void
    {
        foreach (PaymentMethod::cases() as $method) {
            $array = $method->toArray();

            $this->assertIsArray($array);
            $this->assertArrayHasKey('value', $array);
            $this->assertArrayHasKey('label', $array);
            $this->assertEquals($method->value, $array['value']);
            $this->assertEquals($method->getLabel(), $array['label']);
        }
    }
}
