<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\TaxBracket;
use Tourze\SalaryManageBundle\Service\TaxBracketProvider;

/**
 * 税率表提供者测试
 * @internal
 */
#[CoversClass(TaxBracketProvider::class)]
class TaxBracketProviderTest extends TestCase
{
    private TaxBracketProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new TaxBracketProvider();
    }

    public function testGetSalaryTaxBracketsReturnsSevenBrackets(): void
    {
        $brackets = $this->provider->getSalaryTaxBrackets();

        $this->assertCount(7, $brackets);
        $this->assertContainsOnlyInstancesOf(TaxBracket::class, $brackets);
    }

    public function testTaxBracketsHaveCorrectRates(): void
    {
        $brackets = $this->provider->getSalaryTaxBrackets();

        $expectedRates = [0.03, 0.10, 0.20, 0.25, 0.30, 0.35, 0.45];

        foreach ($brackets as $index => $bracket) {
            $this->assertEquals($expectedRates[$index], $bracket->getRate());
        }
    }

    public function testFindApplicableBracketForLowIncome(): void
    {
        // 测试年收入3万元，应该适用第一档3%税率
        $bracket = $this->provider->findApplicableBracket(30000);

        $this->assertNotNull($bracket);
        $this->assertEquals(1, $bracket->getLevel());
        $this->assertEquals(0.03, $bracket->getRate());
    }

    public function testFindApplicableBracketForHighIncome(): void
    {
        // 测试年收入100万元，应该适用第七档45%税率
        $bracket = $this->provider->findApplicableBracket(1000000);

        $this->assertNotNull($bracket);
        $this->assertEquals(7, $bracket->getLevel());
        $this->assertEquals(0.45, $bracket->getRate());
    }

    public function testFindApplicableBracketForZeroIncome(): void
    {
        // 测试零收入，应该返回null
        $bracket = $this->provider->findApplicableBracket(0);

        $this->assertNull($bracket);
    }

    public function testBasicDeductionValues(): void
    {
        $this->assertEquals(60000, $this->provider->getBasicDeduction());
        $this->assertEquals(5000, $this->provider->getMonthlyBasicDeduction());
    }

    public function testValidateTaxBracketsReturnsTrue(): void
    {
        $this->assertTrue($this->provider->validateTaxBrackets());
    }

    public function testTaxBracketsContinuity(): void
    {
        $brackets = $this->provider->getSalaryTaxBrackets();

        // 验证税率档次连续性
        for ($i = 0; $i < count($brackets) - 1; ++$i) {
            $this->assertEquals(
                $brackets[$i]->getMaxIncome(),
                $brackets[$i + 1]->getMinIncome(),
                '税率档次应该连续'
            );
        }
    }

    public function testTaxRatesAreProgressive(): void
    {
        $brackets = $this->provider->getSalaryTaxBrackets();

        // 验证税率递增
        for ($i = 0; $i < count($brackets) - 1; ++$i) {
            $this->assertLessThan(
                $brackets[$i + 1]->getRate(),
                $brackets[$i]->getRate(),
                '税率应该逐级递增'
            );
        }
    }
}
