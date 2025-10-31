<?php

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\ContributionBase;
use Tourze\SalaryManageBundle\Enum\InsuranceType;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * 缴费基数实体测试
 * 验收标准：测试缴费基数的计算逻辑和数据验证
 * @internal
 */
#[CoversClass(ContributionBase::class)]
final class ContributionBaseTest extends TestCase
{
    public function testConstructWithValidDataShouldCreateInstance(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0,
            region: 'beijing',
            year: 2025,
            metadata: ['note' => 'test data']
        );

        $this->assertEquals(InsuranceType::Pension, $contributionBase->getInsuranceType());
        $this->assertEquals(8000.0, $contributionBase->getBaseAmount());
        $this->assertEquals(3000.0, $contributionBase->getMinAmount());
        $this->assertEquals(30000.0, $contributionBase->getMaxAmount());
        $this->assertEquals('beijing', $contributionBase->getRegion());
        $this->assertEquals(2025, $contributionBase->getYear());
        $this->assertEquals(['note' => 'test data'], $contributionBase->getMetadata());
    }

    public function testConstructWithDefaultParametersShouldUseDefaults(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Medical,
            baseAmount: 5000.0,
            minAmount: 3000.0,
            maxAmount: 25000.0
        );

        $this->assertEquals('default', $contributionBase->getRegion());
        $this->assertEquals(2025, $contributionBase->getYear());
        $this->assertEquals([], $contributionBase->getMetadata());
    }

    public function testConstructWithNegativeBaseAmountShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('缴费基数不能为负数');

        new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: -1000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );
    }

    public function testConstructWithNegativeMinAmountShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('缴费基数不能为负数');

        new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: -100.0,
            maxAmount: 30000.0
        );
    }

    public function testConstructWithNegativeMaxAmountShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('缴费基数不能为负数');

        new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: 3000.0,
            maxAmount: -5000.0
        );
    }

    public function testConstructWithMaxAmountLessThanMinAmountShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('最高缴费基数必须大于最低缴费基数');

        new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: 30000.0,
            maxAmount: 25000.0
        );
    }

    public function testConstructWithMaxAmountEqualToMinAmountShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('最高缴费基数必须大于最低缴费基数');

        new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: 25000.0,
            maxAmount: 25000.0
        );
    }

    public function testConstructWithInvalidYearTooLowShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('年度必须在2020-2030之间');

        new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0,
            year: 2019
        );
    }

    public function testConstructWithInvalidYearTooHighShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('年度必须在2020-2030之间');

        new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0,
            year: 2031
        );
    }

    public function testGetActualBaseWithNormalAmountShouldReturnOriginalAmount(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $actualBase = $contributionBase->getActualBase();

        $this->assertEquals(8000.0, $actualBase);
    }

    public function testGetActualBaseWithLowAmountShouldReturnMinAmount(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 2000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $actualBase = $contributionBase->getActualBase();

        $this->assertEquals(3000.0, $actualBase);
    }

    public function testGetActualBaseWithHighAmountShouldReturnMaxAmount(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 35000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $actualBase = $contributionBase->getActualBase();

        $this->assertEquals(30000.0, $actualBase);
    }

    public function testNeedsAdjustmentWithNormalAmountShouldReturnFalse(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $this->assertFalse($contributionBase->needsAdjustment());
    }

    public function testNeedsAdjustmentWithLowAmountShouldReturnTrue(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 2000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $this->assertTrue($contributionBase->needsAdjustment());
    }

    public function testNeedsAdjustmentWithHighAmountShouldReturnTrue(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 35000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $this->assertTrue($contributionBase->needsAdjustment());
    }

    public function testGetAdjustedBaseWithNormalAmountShouldReturnSameInstance(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $adjustedBase = $contributionBase->getAdjustedBase();

        $this->assertSame($contributionBase, $adjustedBase);
    }

    public function testGetAdjustedBaseWithLowAmountShouldReturnNewInstanceWithMinAmount(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 2000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0,
            metadata: ['original' => 'data']
        );

        $adjustedBase = $contributionBase->getAdjustedBase();

        $this->assertNotSame($contributionBase, $adjustedBase);
        $this->assertEquals(3000.0, $adjustedBase->getBaseAmount());
        $this->assertEquals(InsuranceType::Pension, $adjustedBase->getInsuranceType());
        $this->assertEquals(3000.0, $adjustedBase->getMinAmount());
        $this->assertEquals(30000.0, $adjustedBase->getMaxAmount());

        // 验证调整元数据
        $metadata = $adjustedBase->getMetadata();
        $this->assertTrue($metadata['adjusted']);
        $this->assertEquals(2000.0, $metadata['original_base']);
        $this->assertEquals('data', $metadata['original']);
    }

    public function testGetAdjustedBaseWithHighAmountShouldReturnNewInstanceWithMaxAmount(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::HousingFund,
            baseAmount: 35000.0,
            minAmount: 1500.0,
            maxAmount: 25000.0,
            region: 'shanghai'
        );

        $adjustedBase = $contributionBase->getAdjustedBase();

        $this->assertNotSame($contributionBase, $adjustedBase);
        $this->assertEquals(25000.0, $adjustedBase->getBaseAmount());
        $this->assertEquals(InsuranceType::HousingFund, $adjustedBase->getInsuranceType());
        $this->assertEquals('shanghai', $adjustedBase->getRegion());

        // 验证调整元数据
        $metadata = $adjustedBase->getMetadata();
        $this->assertTrue($metadata['adjusted']);
        $this->assertEquals(35000.0, $metadata['original_base']);
    }

    public function testBoundaryValueAtMinAmountShouldNotNeedAdjustment(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 3000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $this->assertFalse($contributionBase->needsAdjustment());
        $this->assertEquals(3000.0, $contributionBase->getActualBase());
    }

    public function testBoundaryValueAtMaxAmountShouldNotNeedAdjustment(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 30000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $this->assertFalse($contributionBase->needsAdjustment());
        $this->assertEquals(30000.0, $contributionBase->getActualBase());
    }

    public function testImmutabilityOfReadonlyClass(): void
    {
        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Medical,
            baseAmount: 2000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $adjustedBase = $contributionBase->getAdjustedBase();

        // 原实例应保持不变
        $this->assertEquals(2000.0, $contributionBase->getBaseAmount());
        $this->assertFalse(isset($contributionBase->getMetadata()['adjusted']));

        // 新实例应有调整后的值
        $this->assertEquals(3000.0, $adjustedBase->getBaseAmount());
        $this->assertTrue($adjustedBase->getMetadata()['adjusted']);
    }

    public function testDifferentInsuranceTypesShouldMaintainTypeIdentity(): void
    {
        $pensionBase = new ContributionBase(
            insuranceType: InsuranceType::Pension,
            baseAmount: 8000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $medicalBase = new ContributionBase(
            insuranceType: InsuranceType::Medical,
            baseAmount: 8000.0,
            minAmount: 3000.0,
            maxAmount: 30000.0
        );

        $this->assertEquals(InsuranceType::Pension, $pensionBase->getInsuranceType());
        $this->assertEquals(InsuranceType::Medical, $medicalBase->getInsuranceType());
        $this->assertNotEquals($pensionBase->getInsuranceType(), $medicalBase->getInsuranceType());
    }

    public function testMetadataMergingInAdjustedBase(): void
    {
        $originalMetadata = [
            'calculation_method' => 'annual_average',
            'source' => 'hr_system',
            'notes' => 'special case',
        ];

        $contributionBase = new ContributionBase(
            insuranceType: InsuranceType::Unemployment,
            baseAmount: 1500.0,
            minAmount: 3000.0,
            maxAmount: 30000.0,
            metadata: $originalMetadata
        );

        $adjustedBase = $contributionBase->getAdjustedBase();
        $metadata = $adjustedBase->getMetadata();

        // 原有元数据应保持
        $this->assertEquals('annual_average', $metadata['calculation_method']);
        $this->assertEquals('hr_system', $metadata['source']);
        $this->assertEquals('special case', $metadata['notes']);

        // 新增调整元数据
        $this->assertTrue($metadata['adjusted']);
        $this->assertEquals(1500.0, $metadata['original_base']);
    }
}
