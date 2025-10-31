<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Service\CalculationRuleInterface;
use Tourze\SalaryManageBundle\Service\SalaryCalculatorInterface;

/**
 * 薪资计算器接口测试
 * 验收标准：验证核心计算接口的契约
 * @internal
 */
#[CoversClass(SalaryCalculatorInterface::class)]
class SalaryCalculatorInterfaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 父类方法调用
    }

    public function testSalaryCalculatorInterfaceExists(): void
    {
        $this->assertTrue(
            interface_exists(SalaryCalculatorInterface::class),
            'SalaryCalculatorInterface must exist'
        );
    }

    public function testSalaryCalculatorInterfaceHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(SalaryCalculatorInterface::class);

        $this->assertTrue(
            $reflection->hasMethod('calculate'),
            'SalaryCalculatorInterface must have calculate method'
        );

        $this->assertTrue(
            $reflection->hasMethod('addRule'),
            'SalaryCalculatorInterface must have addRule method'
        );

        $this->assertTrue(
            $reflection->hasMethod('removeRule'),
            'SalaryCalculatorInterface must have removeRule method'
        );

        $this->assertTrue(
            $reflection->hasMethod('getRules'),
            'SalaryCalculatorInterface must have getRules method'
        );
    }

    public function testCalculationRuleInterfaceExists(): void
    {
        $this->assertTrue(
            interface_exists(CalculationRuleInterface::class),
            'CalculationRuleInterface must exist'
        );
    }

    public function testCalculationRuleInterfaceHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(CalculationRuleInterface::class);

        $this->assertTrue(
            $reflection->hasMethod('getType'),
            'CalculationRuleInterface must have getType method'
        );

        $this->assertTrue(
            $reflection->hasMethod('calculate'),
            'CalculationRuleInterface must have calculate method'
        );

        $this->assertTrue(
            $reflection->hasMethod('isApplicable'),
            'CalculationRuleInterface must have isApplicable method'
        );

        $this->assertTrue(
            $reflection->hasMethod('getOrder'),
            'CalculationRuleInterface must have getOrder method'
        );
    }
}
