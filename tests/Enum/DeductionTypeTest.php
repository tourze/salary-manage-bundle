<?php

namespace Tourze\SalaryManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SalaryManageBundle\Enum\DeductionType;

/**
 * @internal
 */
#[CoversClass(DeductionType::class)]
class DeductionTypeTest extends AbstractEnumTestCase
{
    public function testAllDeductionTypes(): void
    {
        $expectedTypes = [
            'child_education',
            'continuing_education',
            'serious_illness',
            'housing_loan',
            'housing_rent',
            'elder_care',
        ];

        $actualTypes = array_map(fn (DeductionType $type) => $type->value, DeductionType::cases());

        $this->assertCount(6, DeductionType::cases());
        $this->assertEquals($expectedTypes, $actualTypes);
    }

    public function testGetLabelForChildEducation(): void
    {
        $this->assertEquals('子女教育', DeductionType::ChildEducation->getLabel());
    }

    public function testGetLabelForContinuingEducation(): void
    {
        $this->assertEquals('继续教育', DeductionType::ContinuingEducation->getLabel());
    }

    public function testGetLabelForSeriousIllness(): void
    {
        $this->assertEquals('大病医疗', DeductionType::SeriousIllness->getLabel());
    }

    public function testGetLabelForHousingLoan(): void
    {
        $this->assertEquals('住房贷款利息', DeductionType::HousingLoan->getLabel());
    }

    public function testGetLabelForHousingRent(): void
    {
        $this->assertEquals('住房租金', DeductionType::HousingRent->getLabel());
    }

    public function testGetLabelForElderCare(): void
    {
        $this->assertEquals('赡养老人', DeductionType::ElderCare->getLabel());
    }

    public function testGetMonthlyLimitForChildEducation(): void
    {
        $this->assertEquals(2000, DeductionType::ChildEducation->getMonthlyLimit());
    }

    public function testGetMonthlyLimitForContinuingEducation(): void
    {
        $this->assertEquals(400, DeductionType::ContinuingEducation->getMonthlyLimit());
    }

    public function testGetMonthlyLimitForSeriousIllness(): void
    {
        $this->assertEquals(6666.67, DeductionType::SeriousIllness->getMonthlyLimit());
    }

    public function testGetMonthlyLimitForHousingLoan(): void
    {
        $this->assertEquals(1000, DeductionType::HousingLoan->getMonthlyLimit());
    }

    public function testGetMonthlyLimitForHousingRent(): void
    {
        $this->assertEquals(1500, DeductionType::HousingRent->getMonthlyLimit());
    }

    public function testGetMonthlyLimitForElderCare(): void
    {
        $this->assertEquals(3000, DeductionType::ElderCare->getMonthlyLimit());
    }

    public function testEnumValues(): void
    {
        $this->assertEquals('child_education', DeductionType::ChildEducation->value);
        $this->assertEquals('continuing_education', DeductionType::ContinuingEducation->value);
        $this->assertEquals('serious_illness', DeductionType::SeriousIllness->value);
        $this->assertEquals('housing_loan', DeductionType::HousingLoan->value);
        $this->assertEquals('housing_rent', DeductionType::HousingRent->value);
        $this->assertEquals('elder_care', DeductionType::ElderCare->value);
    }

    public function testImplementsItemable(): void
    {
        $this->assertContains('Tourze\EnumExtra\Itemable', class_implements(DeductionType::class));
    }

    public function testImplementsLabelable(): void
    {
        $this->assertContains('Tourze\EnumExtra\Labelable', class_implements(DeductionType::class));
    }

    public function testImplementsSelectable(): void
    {
        $this->assertContains('Tourze\EnumExtra\Selectable', class_implements(DeductionType::class));
    }

    public function testUsesItemTrait(): void
    {
        $this->assertContains('Tourze\EnumExtra\ItemTrait', class_uses(DeductionType::class));
    }

    public function testUsesSelectTrait(): void
    {
        $this->assertContains('Tourze\EnumExtra\SelectTrait', class_uses(DeductionType::class));
    }

    public function testFromMethodWithValidValue(): void
    {
        $this->assertEquals(DeductionType::ChildEducation, DeductionType::from('child_education'));
        $this->assertEquals(DeductionType::ContinuingEducation, DeductionType::from('continuing_education'));
        $this->assertEquals(DeductionType::SeriousIllness, DeductionType::from('serious_illness'));
        $this->assertEquals(DeductionType::HousingLoan, DeductionType::from('housing_loan'));
        $this->assertEquals(DeductionType::HousingRent, DeductionType::from('housing_rent'));
        $this->assertEquals(DeductionType::ElderCare, DeductionType::from('elder_care'));
    }

    public function testTryFromMethodWithValidValue(): void
    {
        $this->assertEquals(DeductionType::ChildEducation, DeductionType::tryFrom('child_education'));
        $this->assertEquals(DeductionType::ContinuingEducation, DeductionType::tryFrom('continuing_education'));
        $this->assertNull(DeductionType::tryFrom('invalid_type'));
    }

    public function testLabelAndLimitCorrespondence(): void
    {
        foreach (DeductionType::cases() as $type) {
            $this->assertIsString($type->getLabel());
            $this->assertNotEmpty($type->getLabel());
            $this->assertIsFloat($type->getMonthlyLimit());
            $this->assertGreaterThan(0, $type->getMonthlyLimit());
        }
    }

    public function testSeriousIllnessYearlyLimitCalculation(): void
    {
        // 验证大病医疗的月度限额是年度限额除以12
        $yearlyLimit = 80000;
        $monthlyLimit = $yearlyLimit / 12;

        $this->assertEqualsWithDelta($monthlyLimit, DeductionType::SeriousIllness->getMonthlyLimit(), 0.01);
    }

    public function testToArray(): void
    {
        foreach (DeductionType::cases() as $type) {
            $array = $type->toArray();

            $this->assertIsArray($array);
            $this->assertArrayHasKey('value', $array);
            $this->assertArrayHasKey('label', $array);
            $this->assertEquals($type->value, $array['value']);
            $this->assertEquals($type->getLabel(), $array['label']);
        }
    }
}
