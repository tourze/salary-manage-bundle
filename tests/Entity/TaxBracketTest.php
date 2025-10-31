<?php

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\TaxBracket;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * @internal
 */
#[CoversClass(TaxBracket::class)]
final class TaxBracketTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 父类方法调用
    }

    public function testConstructorWithValidParameters(): void
    {
        $bracket = new TaxBracket(
            1,        // level
            0.0,      // minIncome
            3000.0,   // maxIncome
            0.03,     // rate (3%)
            0.0       // quickDeduction
        );

        $this->assertEquals(1, $bracket->getLevel());
        $this->assertEquals(0.0, $bracket->getMinIncome());
        $this->assertEquals(3000.0, $bracket->getMaxIncome());
        $this->assertEquals(0.03, $bracket->getRate());
        $this->assertEquals(0.0, $bracket->getQuickDeduction());
    }

    public function testConstructorWithInfiniteMaxIncome(): void
    {
        $bracket = new TaxBracket(
            7,        // level
            960000.0, // minIncome
            INF,      // maxIncome (最高档次)
            0.45,     // rate (45%)
            181920.0  // quickDeduction
        );

        $this->assertEquals(7, $bracket->getLevel());
        $this->assertEquals(960000.0, $bracket->getMinIncome());
        $this->assertEquals(INF, $bracket->getMaxIncome());
        $this->assertEquals(0.45, $bracket->getRate());
        $this->assertEquals(181920.0, $bracket->getQuickDeduction());
    }

    #[TestWith([0])]
    #[TestWith([-1])]
    #[TestWith([8])]
    #[TestWith([10])]
    public function testConstructorThrowsExceptionForInvalidLevel(int $level): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('税率档次必须在1-7之间');

        new TaxBracket($level, 0.0, 3000.0, 0.03, 0.0);
    }

    #[TestWith([-0.01])]
    #[TestWith([1.01])]
    #[TestWith([2.0])]
    public function testConstructorThrowsExceptionForInvalidRate(float $rate): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('税率必须在0-1之间');

        new TaxBracket(1, 0.0, 3000.0, $rate, 0.0);
    }

    #[TestWith([-1.0, 3000.0, 0.0])]
    #[TestWith([0.0, -1.0, 0.0])]
    #[TestWith([0.0, 3000.0, -1.0])]
    public function testConstructorThrowsExceptionForNegativeValues(
        float $minIncome,
        float $maxIncome,
        float $quickDeduction,
    ): void {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('收入和速算扣除数不能为负数');

        new TaxBracket(1, $minIncome, $maxIncome, 0.03, $quickDeduction);
    }

    public function testConstructorThrowsExceptionForInvalidIncomeRange(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('最高收入必须大于最低收入');

        new TaxBracket(1, 3000.0, 2000.0, 0.03, 0.0); // max < min
    }

    public function testConstructorThrowsExceptionForEqualIncomeRange(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('最高收入必须大于最低收入');

        new TaxBracket(1, 3000.0, 3000.0, 0.03, 0.0); // max == min
    }

    #[TestWith([0.0, 3000.0, 0.0, false], 'first bracket below min')]
    #[TestWith([0.0, 3000.0, 1500.0, true], 'first bracket within')]
    #[TestWith([0.0, 3000.0, 3000.0, true], 'first bracket at max')]
    #[TestWith([0.0, 3000.0, 3500.0, false], 'first bracket above max')]
    #[TestWith([12000.0, 25000.0, 10000.0, false], 'middle bracket below min')]
    #[TestWith([12000.0, 25000.0, 12000.0, false], 'middle bracket at min')]
    #[TestWith([12000.0, 25000.0, 12000.01, true], 'middle bracket just above min')]
    #[TestWith([12000.0, 25000.0, 18000.0, true], 'middle bracket within')]
    #[TestWith([12000.0, 25000.0, 25000.0, true], 'middle bracket at max')]
    #[TestWith([12000.0, 25000.0, 30000.0, false], 'middle bracket above max')]
    #[TestWith([960000.0, INF, 500000.0, false], 'highest bracket below min')]
    #[TestWith([960000.0, INF, 960000.0, false], 'highest bracket at min')]
    #[TestWith([960000.0, INF, 1000000.0, true], 'highest bracket above min')]
    #[TestWith([960000.0, INF, 10000000.0, true], 'highest bracket very high')]
    public function testIsApplicable(
        float $minIncome,
        float $maxIncome,
        float $testIncome,
        bool $expectedApplicable,
    ): void {
        $bracket = new TaxBracket(1, $minIncome, $maxIncome, 0.03, 0.0);

        $this->assertEquals($expectedApplicable, $bracket->isApplicable($testIncome));
    }

    #[TestWith([1, 0.0, 3000.0, 0.03, 0.0, 2000.0, 60.0], 'first bracket 2000')]
    #[TestWith([1, 0.0, 3000.0, 0.03, 0.0, 3000.0, 90.0], 'first bracket 3000')]
    #[TestWith([2, 3000.0, 12000.0, 0.10, 210.0, 5000.0, 290.0], 'second bracket 5000')]
    #[TestWith([2, 3000.0, 12000.0, 0.10, 210.0, 10000.0, 790.0], 'second bracket 10000')]
    #[TestWith([7, 960000.0, INF, 0.45, 181920.0, 1000000.0, 268080.0], 'seventh bracket 1000000')]
    #[TestWith([2, 3000.0, 12000.0, 0.10, 210.0, 2000.0, 0.0], 'below range')]
    #[TestWith([2, 3000.0, 12000.0, 0.10, 210.0, 15000.0, 0.0], 'above range')]
    public function testCalculateTax(
        int $level,
        float $minIncome,
        float $maxIncome,
        float $rate,
        float $quickDeduction,
        float $income,
        float $expectedTax,
    ): void {
        $bracket = new TaxBracket($level, $minIncome, $maxIncome, $rate, $quickDeduction);

        $this->assertEquals($expectedTax, $bracket->calculateTax($income));
    }

    public function testCalculateTaxForZeroIncome(): void
    {
        $bracket = new TaxBracket(1, 0.0, 3000.0, 0.03, 0.0);

        $this->assertEquals(0.0, $bracket->calculateTax(0.0));
    }

    public function testCalculateTaxWithHighQuickDeduction(): void
    {
        // 测试速算扣除数较大的情况，可能导致负税额
        $bracket = new TaxBracket(2, 3000.0, 12000.0, 0.10, 500.0);

        // 收入4000，应税额：4000 * 0.10 - 500 = -100
        // 但由于不适用，应该返回0
        $this->assertEquals(-100.0, $bracket->calculateTax(4000.0));
    }

    public function testRealWorldTaxBrackets(): void
    {
        // 2024年个人所得税税率表（年度）
        $brackets = [
            new TaxBracket(1, 0, 36000, 0.03, 0),
            new TaxBracket(2, 36000, 144000, 0.10, 2520),
            new TaxBracket(3, 144000, 300000, 0.20, 16920),
            new TaxBracket(4, 300000, 420000, 0.25, 31920),
            new TaxBracket(5, 420000, 660000, 0.30, 52920),
            new TaxBracket(6, 660000, 960000, 0.35, 85920),
            new TaxBracket(7, 960000, INF, 0.45, 181920),
        ];

        // 测试年收入100,000元
        $income = 100000;
        $applicableBracket = null;

        foreach ($brackets as $bracket) {
            if ($bracket->isApplicable($income)) {
                $applicableBracket = $bracket;
                break;
            }
        }

        $this->assertNotNull($applicableBracket);
        $this->assertEquals(2, $applicableBracket->getLevel());

        $expectedTax = 100000 * 0.10 - 2520; // 7480
        $this->assertEquals(7480.0, $applicableBracket->calculateTax($income));
    }

    public function testBoundaryConditions(): void
    {
        $bracket = new TaxBracket(2, 3000.0, 12000.0, 0.10, 210.0);

        // 测试边界值
        $this->assertFalse($bracket->isApplicable(3000.0));   // 等于最低收入
        $this->assertTrue($bracket->isApplicable(3000.01));   // 刚好超过最低收入
        $this->assertTrue($bracket->isApplicable(12000.0));   // 等于最高收入
        $this->assertFalse($bracket->isApplicable(12000.01)); // 刚好超过最高收入
    }

    public function testFloatingPointPrecision(): void
    {
        $bracket = new TaxBracket(1, 0.0, 3000.0, 0.03, 0.0);

        // 测试浮点数精度
        $income = 2999.99;
        $expectedTax = $income * 0.03; // 89.9997

        $this->assertEqualsWithDelta($expectedTax, $bracket->calculateTax($income), 0.0001);
    }

    public function testValidTaxRateBoundaries(): void
    {
        // 测试税率边界值
        $zeroRateBracket = new TaxBracket(1, 0.0, 3000.0, 0.0, 0.0);
        $fullRateBracket = new TaxBracket(7, 960000.0, INF, 1.0, 181920.0);

        $this->assertEquals(0.0, $zeroRateBracket->getRate());
        $this->assertEquals(1.0, $fullRateBracket->getRate());

        $this->assertEquals(0.0, $zeroRateBracket->calculateTax(1000.0));
        $this->assertEquals(818080.0, $fullRateBracket->calculateTax(1000000.0)); // 1000000 * 1.0 - 181920
    }

    public function testReadOnlyNature(): void
    {
        $bracket = new TaxBracket(3, 144000.0, 300000.0, 0.20, 16920.0);

        // 验证所有属性都有对应的 getter 方法
        $this->assertIsInt($bracket->getLevel());
        $this->assertIsFloat($bracket->getMinIncome());
        $this->assertIsFloat($bracket->getMaxIncome());
        $this->assertIsFloat($bracket->getRate());
        $this->assertIsFloat($bracket->getQuickDeduction());
        $this->assertIsBool($bracket->isApplicable(200000.0));
        $this->assertIsFloat($bracket->calculateTax(200000.0));
    }

    public function testAllSevenLevels(): void
    {
        for ($level = 1; $level <= 7; ++$level) {
            $bracket = new TaxBracket(
                $level,
                $level * 1000.0,
                ($level + 1) * 1000.0,
                $level * 0.05,
                $level * 100.0
            );

            $this->assertEquals($level, $bracket->getLevel());
            $this->assertIsFloat($bracket->getRate());
            $this->assertGreaterThan(0, $bracket->getRate());
            $this->assertLessThanOrEqual(1.0, $bracket->getRate());
        }
    }
}
