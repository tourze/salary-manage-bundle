<?php

namespace Tourze\SalaryManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SalaryManageBundle\Enum\InsuranceType;

/**
 * @internal
 */
#[CoversClass(InsuranceType::class)]
class InsuranceTypeTest extends AbstractEnumTestCase
{
    public function testAllInsuranceTypes(): void
    {
        $expectedTypes = [
            'pension',
            'medical',
            'unemployment',
            'work_injury',
            'maternity',
            'housing_fund',
        ];

        $actualTypes = array_map(fn (InsuranceType $type) => $type->value, InsuranceType::cases());

        $this->assertCount(6, InsuranceType::cases());
        $this->assertEquals($expectedTypes, $actualTypes);
    }

    public function testGetLabelForPension(): void
    {
        $this->assertEquals('养老保险', InsuranceType::Pension->getLabel());
    }

    public function testGetLabelForMedical(): void
    {
        $this->assertEquals('医疗保险', InsuranceType::Medical->getLabel());
    }

    public function testGetLabelForUnemployment(): void
    {
        $this->assertEquals('失业保险', InsuranceType::Unemployment->getLabel());
    }

    public function testGetLabelForWorkInjury(): void
    {
        $this->assertEquals('工伤保险', InsuranceType::WorkInjury->getLabel());
    }

    public function testGetLabelForMaternity(): void
    {
        $this->assertEquals('生育保险', InsuranceType::Maternity->getLabel());
    }

    public function testGetLabelForHousingFund(): void
    {
        $this->assertEquals('住房公积金', InsuranceType::HousingFund->getLabel());
    }

    public function testGetStandardEmployerRateForPension(): void
    {
        $this->assertEquals(0.20, InsuranceType::Pension->getStandardEmployerRate());
    }

    public function testGetStandardEmployerRateForMedical(): void
    {
        $this->assertEquals(0.08, InsuranceType::Medical->getStandardEmployerRate());
    }

    public function testGetStandardEmployerRateForUnemployment(): void
    {
        $this->assertEquals(0.007, InsuranceType::Unemployment->getStandardEmployerRate());
    }

    public function testGetStandardEmployerRateForWorkInjury(): void
    {
        $this->assertEquals(0.005, InsuranceType::WorkInjury->getStandardEmployerRate());
    }

    public function testGetStandardEmployerRateForMaternity(): void
    {
        $this->assertEquals(0.008, InsuranceType::Maternity->getStandardEmployerRate());
    }

    public function testGetStandardEmployerRateForHousingFund(): void
    {
        $this->assertEquals(0.12, InsuranceType::HousingFund->getStandardEmployerRate());
    }

    public function testGetStandardEmployeeRateForPension(): void
    {
        $this->assertEquals(0.08, InsuranceType::Pension->getStandardEmployeeRate());
    }

    public function testGetStandardEmployeeRateForMedical(): void
    {
        $this->assertEquals(0.02, InsuranceType::Medical->getStandardEmployeeRate());
    }

    public function testGetStandardEmployeeRateForUnemployment(): void
    {
        $this->assertEquals(0.003, InsuranceType::Unemployment->getStandardEmployeeRate());
    }

    public function testGetStandardEmployeeRateForWorkInjury(): void
    {
        $this->assertEquals(0.0, InsuranceType::WorkInjury->getStandardEmployeeRate());
    }

    public function testGetStandardEmployeeRateForMaternity(): void
    {
        $this->assertEquals(0.0, InsuranceType::Maternity->getStandardEmployeeRate());
    }

    public function testGetStandardEmployeeRateForHousingFund(): void
    {
        $this->assertEquals(0.12, InsuranceType::HousingFund->getStandardEmployeeRate());
    }

    public function testIsSocialInsuranceForPension(): void
    {
        $this->assertTrue(InsuranceType::Pension->isSocialInsurance());
    }

    public function testIsSocialInsuranceForMedical(): void
    {
        $this->assertTrue(InsuranceType::Medical->isSocialInsurance());
    }

    public function testIsSocialInsuranceForUnemployment(): void
    {
        $this->assertTrue(InsuranceType::Unemployment->isSocialInsurance());
    }

    public function testIsSocialInsuranceForWorkInjury(): void
    {
        $this->assertTrue(InsuranceType::WorkInjury->isSocialInsurance());
    }

    public function testIsSocialInsuranceForMaternity(): void
    {
        $this->assertTrue(InsuranceType::Maternity->isSocialInsurance());
    }

    public function testIsSocialInsuranceForHousingFund(): void
    {
        $this->assertFalse(InsuranceType::HousingFund->isSocialInsurance());
    }

    public function testIsTaxDeductibleForAllTypes(): void
    {
        foreach (InsuranceType::cases() as $type) {
            $this->assertTrue($type->isTaxDeductible(), "Insurance type {$type->value} should be tax deductible");
        }
    }

    public function testEnumValues(): void
    {
        $this->assertEquals('pension', InsuranceType::Pension->value);
        $this->assertEquals('medical', InsuranceType::Medical->value);
        $this->assertEquals('unemployment', InsuranceType::Unemployment->value);
        $this->assertEquals('work_injury', InsuranceType::WorkInjury->value);
        $this->assertEquals('maternity', InsuranceType::Maternity->value);
        $this->assertEquals('housing_fund', InsuranceType::HousingFund->value);
    }

    public function testImplementsItemable(): void
    {
        $this->assertContains('Tourze\EnumExtra\Itemable', class_implements(InsuranceType::class));
    }

    public function testImplementsLabelable(): void
    {
        $this->assertContains('Tourze\EnumExtra\Labelable', class_implements(InsuranceType::class));
    }

    public function testImplementsSelectable(): void
    {
        $this->assertContains('Tourze\EnumExtra\Selectable', class_implements(InsuranceType::class));
    }

    public function testUsesItemTrait(): void
    {
        $this->assertContains('Tourze\EnumExtra\ItemTrait', class_uses(InsuranceType::class));
    }

    public function testUsesSelectTrait(): void
    {
        $this->assertContains('Tourze\EnumExtra\SelectTrait', class_uses(InsuranceType::class));
    }

    public function testFromMethodWithValidValue(): void
    {
        $this->assertEquals(InsuranceType::Pension, InsuranceType::from('pension'));
        $this->assertEquals(InsuranceType::Medical, InsuranceType::from('medical'));
        $this->assertEquals(InsuranceType::Unemployment, InsuranceType::from('unemployment'));
        $this->assertEquals(InsuranceType::WorkInjury, InsuranceType::from('work_injury'));
        $this->assertEquals(InsuranceType::Maternity, InsuranceType::from('maternity'));
        $this->assertEquals(InsuranceType::HousingFund, InsuranceType::from('housing_fund'));
    }

    public function testTryFromMethodWithValidValue(): void
    {
        $this->assertEquals(InsuranceType::Pension, InsuranceType::tryFrom('pension'));
        $this->assertEquals(InsuranceType::Medical, InsuranceType::tryFrom('medical'));
        $this->assertNull(InsuranceType::tryFrom('invalid_type'));
    }

    public function testEmployerAndEmployeeRateCorrespondence(): void
    {
        foreach (InsuranceType::cases() as $type) {
            $employerRate = $type->getStandardEmployerRate();
            $employeeRate = $type->getStandardEmployeeRate();

            $this->assertIsFloat($employerRate);
            $this->assertIsFloat($employeeRate);
            $this->assertGreaterThanOrEqual(0, $employerRate);
            $this->assertGreaterThanOrEqual(0, $employeeRate);

            // 工伤和生育保险个人不缴费
            if (in_array($type, [InsuranceType::WorkInjury, InsuranceType::Maternity], true)) {
                $this->assertEquals(0.0, $employeeRate);
                $this->assertGreaterThan(0, $employerRate);
            }

            // 住房公积金企业和个人缴费比例相同
            if (InsuranceType::HousingFund === $type) {
                $this->assertEquals($employerRate, $employeeRate);
            }
        }
    }

    public function testSocialInsuranceVsHousingFund(): void
    {
        $socialInsuranceCount = 0;
        $housingFundCount = 0;

        foreach (InsuranceType::cases() as $type) {
            if ($type->isSocialInsurance()) {
                ++$socialInsuranceCount;
            } else {
                ++$housingFundCount;
            }
        }

        $this->assertEquals(5, $socialInsuranceCount, '应该有5项社会保险');
        $this->assertEquals(1, $housingFundCount, '应该有1项住房公积金');
    }

    public function testRateRangeValidation(): void
    {
        foreach (InsuranceType::cases() as $type) {
            $employerRate = $type->getStandardEmployerRate();
            $employeeRate = $type->getStandardEmployeeRate();

            // 缴费比例应该在合理范围内（0-50%）
            $this->assertLessThanOrEqual(0.5, $employerRate, "Employer rate for {$type->value} should not exceed 50%");
            $this->assertLessThanOrEqual(0.5, $employeeRate, "Employee rate for {$type->value} should not exceed 50%");
        }
    }

    public function testToArray(): void
    {
        foreach (InsuranceType::cases() as $type) {
            $array = $type->toArray();

            $this->assertIsArray($array);
            $this->assertArrayHasKey('value', $array);
            $this->assertArrayHasKey('label', $array);
            $this->assertEquals($type->value, $array['value']);
            $this->assertEquals($type->getLabel(), $array['label']);
        }
    }
}
