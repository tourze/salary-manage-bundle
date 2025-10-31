<?php

namespace Tourze\SalaryManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;

/**
 * @internal
 */
#[CoversClass(SalaryItemType::class)]
class SalaryItemTypeTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('basic_salary', SalaryItemType::BasicSalary->value);
        $this->assertEquals('performance_bonus', SalaryItemType::PerformanceBonus->value);
        $this->assertEquals('bonus', SalaryItemType::Bonus->value);
        $this->assertEquals('allowance', SalaryItemType::Allowance->value);
        $this->assertEquals('subsidy', SalaryItemType::Subsidy->value);
        $this->assertEquals('overtime', SalaryItemType::Overtime->value);
        $this->assertEquals('commission', SalaryItemType::Commission->value);
        $this->assertEquals('special_reward', SalaryItemType::SpecialReward->value);
        $this->assertEquals('transport_allowance', SalaryItemType::TransportAllowance->value);
        $this->assertEquals('meal_allowance', SalaryItemType::MealAllowance->value);
        $this->assertEquals('social_insurance', SalaryItemType::SocialInsurance->value);
        $this->assertEquals('income_tax', SalaryItemType::IncomeTax->value);
    }

    #[TestWith(['basic_salary', '基本工资'])]
    #[TestWith(['performance_bonus', '绩效工资'])]
    #[TestWith(['bonus', '奖金'])]
    #[TestWith(['allowance', '津贴'])]
    #[TestWith(['subsidy', '补贴'])]
    #[TestWith(['overtime', '加班费'])]
    #[TestWith(['commission', '提成'])]
    #[TestWith(['special_reward', '专项奖励'])]
    #[TestWith(['transport_allowance', '交通补助'])]
    #[TestWith(['meal_allowance', '餐饮补助'])]
    #[TestWith(['social_insurance', '社会保险'])]
    #[TestWith(['income_tax', '个人所得税'])]
    public function testGetDisplayName(string $typeValue, string $expectedDisplayName): void
    {
        $type = SalaryItemType::from($typeValue);
        $this->assertEquals($expectedDisplayName, $type->getDisplayName());
    }

    #[TestWith(['basic_salary', false])]
    #[TestWith(['performance_bonus', false])]
    #[TestWith(['bonus', false])]
    #[TestWith(['allowance', false])]
    #[TestWith(['subsidy', false])]
    #[TestWith(['overtime', false])]
    #[TestWith(['commission', false])]
    #[TestWith(['special_reward', false])]
    #[TestWith(['transport_allowance', false])]
    #[TestWith(['meal_allowance', false])]
    #[TestWith(['social_insurance', true])]
    #[TestWith(['income_tax', true])]
    public function testIsDeduction(string $typeValue, bool $expectedIsDeduction): void
    {
        $type = SalaryItemType::from($typeValue);
        $this->assertEquals($expectedIsDeduction, $type->isDeduction());
    }

    public function testGetLabel(): void
    {
        // 测试 Labelable 接口的实现
        $type = SalaryItemType::BasicSalary;
        $this->assertEquals($type->getDisplayName(), $type->getLabel());
    }

    public function testAllTypesHaveDisplayNames(): void
    {
        $cases = SalaryItemType::cases();

        foreach ($cases as $type) {
            $displayName = $type->getDisplayName();
            $this->assertNotEmpty($displayName, "Type {$type->value} should have a non-empty display name");
            $this->assertIsString($displayName);
        }
    }

    public function testIncomeTypes(): void
    {
        $incomeTypes = [
            SalaryItemType::BasicSalary,
            SalaryItemType::PerformanceBonus,
            SalaryItemType::Bonus,
            SalaryItemType::Allowance,
            SalaryItemType::Subsidy,
            SalaryItemType::Overtime,
            SalaryItemType::Commission,
            SalaryItemType::SpecialReward,
            SalaryItemType::TransportAllowance,
            SalaryItemType::MealAllowance,
        ];

        foreach ($incomeTypes as $type) {
            $this->assertFalse($type->isDeduction(), "{$type->value} should not be a deduction");
        }
    }

    public function testDeductionTypes(): void
    {
        $deductionTypes = [
            SalaryItemType::SocialInsurance,
            SalaryItemType::IncomeTax,
        ];

        foreach ($deductionTypes as $type) {
            $this->assertTrue($type->isDeduction(), "{$type->value} should be a deduction");
        }
    }

    public function testItemableImplementation(): void
    {
        // 测试每个类型都实现了 Itemable 接口
        $cases = SalaryItemType::cases();

        foreach ($cases as $type) {
            $this->assertIsString($type->getLabel());
            $this->assertEquals($type->getDisplayName(), $type->getLabel());
        }
    }

    public function testEnumCases(): void
    {
        $cases = SalaryItemType::cases();

        $this->assertCount(12, $cases);
        $this->assertContains(SalaryItemType::BasicSalary, $cases);
        $this->assertContains(SalaryItemType::PerformanceBonus, $cases);
        $this->assertContains(SalaryItemType::Bonus, $cases);
        $this->assertContains(SalaryItemType::Allowance, $cases);
        $this->assertContains(SalaryItemType::Subsidy, $cases);
        $this->assertContains(SalaryItemType::Overtime, $cases);
        $this->assertContains(SalaryItemType::Commission, $cases);
        $this->assertContains(SalaryItemType::SpecialReward, $cases);
        $this->assertContains(SalaryItemType::TransportAllowance, $cases);
        $this->assertContains(SalaryItemType::MealAllowance, $cases);
        $this->assertContains(SalaryItemType::SocialInsurance, $cases);
        $this->assertContains(SalaryItemType::IncomeTax, $cases);
    }

    public function testFromStringValue(): void
    {
        $this->assertEquals(SalaryItemType::BasicSalary, SalaryItemType::from('basic_salary'));
        $this->assertEquals(SalaryItemType::PerformanceBonus, SalaryItemType::from('performance_bonus'));
        $this->assertEquals(SalaryItemType::SocialInsurance, SalaryItemType::from('social_insurance'));
        $this->assertEquals(SalaryItemType::IncomeTax, SalaryItemType::from('income_tax'));
    }

    public function testTryFromStringValue(): void
    {
        $this->assertEquals(SalaryItemType::BasicSalary, SalaryItemType::tryFrom('basic_salary'));
        $this->assertEquals(SalaryItemType::Bonus, SalaryItemType::tryFrom('bonus'));
        $this->assertNull(SalaryItemType::tryFrom('invalid_type'));
    }

    public function testTypeComparison(): void
    {
        $basicSalary1 = SalaryItemType::BasicSalary;
        $basicSalary2 = SalaryItemType::BasicSalary;
        $bonus = SalaryItemType::Bonus;

        $this->assertEquals($basicSalary1, $basicSalary2);
        $this->assertNotEquals($basicSalary1, $bonus);
    }

    public function testUniqueValues(): void
    {
        $cases = SalaryItemType::cases();
        $values = array_map(fn ($case) => $case->value, $cases);

        $this->assertEquals(count($values), count(array_unique($values)), 'All enum values should be unique');
    }

    public function testAtLeastTenTypes(): void
    {
        // 根据注释要求，应该支持至少10种薪资项目类型
        $cases = SalaryItemType::cases();
        $this->assertGreaterThanOrEqual(10, count($cases), 'Should support at least 10 salary item types');
    }

    public function testAllowanceTypes(): void
    {
        $allowanceTypes = [
            SalaryItemType::Allowance,
            SalaryItemType::TransportAllowance,
            SalaryItemType::MealAllowance,
        ];

        foreach ($allowanceTypes as $type) {
            $this->assertFalse($type->isDeduction());
            // 津贴、补助都是非扣除项目
            $displayName = $type->getDisplayName();
            $this->assertTrue(
                str_contains($displayName, '津贴') || str_contains($displayName, '补助'),
                "Allowance type {$displayName} should contain '津贴' or '补助'"
            );
        }
    }

    public function testBonusTypes(): void
    {
        $bonusTypes = [
            SalaryItemType::Bonus,
            SalaryItemType::PerformanceBonus,
            SalaryItemType::SpecialReward,
        ];

        foreach ($bonusTypes as $type) {
            $this->assertFalse($type->isDeduction());
        }
    }

    public function testBasicSalaryType(): void
    {
        $basicSalary = SalaryItemType::BasicSalary;

        $this->assertEquals('basic_salary', $basicSalary->value);
        $this->assertEquals('基本工资', $basicSalary->getDisplayName());
        $this->assertFalse($basicSalary->isDeduction());
        $this->assertEquals($basicSalary->getDisplayName(), $basicSalary->getLabel());
    }

    public function testOvertimeType(): void
    {
        $overtime = SalaryItemType::Overtime;

        $this->assertEquals('overtime', $overtime->value);
        $this->assertEquals('加班费', $overtime->getDisplayName());
        $this->assertFalse($overtime->isDeduction());
    }

    public function testCommissionType(): void
    {
        $commission = SalaryItemType::Commission;

        $this->assertEquals('commission', $commission->value);
        $this->assertEquals('提成', $commission->getDisplayName());
        $this->assertFalse($commission->isDeduction());
    }

    public function testSocialInsuranceType(): void
    {
        $socialInsurance = SalaryItemType::SocialInsurance;

        $this->assertEquals('social_insurance', $socialInsurance->value);
        $this->assertEquals('社会保险', $socialInsurance->getDisplayName());
        $this->assertTrue($socialInsurance->isDeduction());
    }

    public function testIncomeTaxType(): void
    {
        $incomeTax = SalaryItemType::IncomeTax;

        $this->assertEquals('income_tax', $incomeTax->value);
        $this->assertEquals('个人所得税', $incomeTax->getDisplayName());
        $this->assertTrue($incomeTax->isDeduction());
    }

    public function testDisplayNameUniqueness(): void
    {
        $cases = SalaryItemType::cases();
        $displayNames = array_map(fn ($case) => $case->getDisplayName(), $cases);

        $this->assertEquals(
            count($displayNames),
            count(array_unique($displayNames)),
            'All display names should be unique'
        );
    }

    public function testToArray(): void
    {
        foreach (SalaryItemType::cases() as $type) {
            $array = $type->toArray();

            $this->assertIsArray($array);
            $this->assertArrayHasKey('value', $array);
            $this->assertArrayHasKey('label', $array);
            $this->assertEquals($type->value, $array['value']);
            $this->assertEquals($type->getLabel(), $array['label']);
        }
    }
}
