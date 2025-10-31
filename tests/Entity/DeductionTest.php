<?php

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Entity\Deduction;
use Tourze\SalaryManageBundle\Enum\DeductionType;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * 专项附加扣除实体测试
 * 验收标准：测试个人所得税专项附加扣除的验证逻辑
 * @internal
 */
#[CoversClass(Deduction::class)]
final class DeductionTest extends TestCase
{
    public function testConstructWithValidChildEducationDeductionShouldCreateInstance(): void
    {
        $deduction = new Deduction(
            type: DeductionType::ChildEducation,
            amount: 2000.0,
            description: '子女教育扣除 - 小学阶段',
            metadata: ['child_name' => '张小明', 'school' => '某某小学']
        );

        $this->assertEquals(DeductionType::ChildEducation, $deduction->getType());
        $this->assertEquals(2000.0, $deduction->getAmount());
        $this->assertEquals('子女教育扣除 - 小学阶段', $deduction->getDescription());
        $this->assertEquals(['child_name' => '张小明', 'school' => '某某小学'], $deduction->getMetadata());
    }

    public function testConstructWithDefaultParametersShouldUseDefaults(): void
    {
        $deduction = new Deduction(
            type: DeductionType::HousingLoan,
            amount: 1000.0
        );

        $this->assertEquals('', $deduction->getDescription());
        $this->assertEquals([], $deduction->getMetadata());
    }

    public function testConstructWithNegativeAmountShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('扣除金额不能为负数');

        new Deduction(
            type: DeductionType::ChildEducation,
            amount: -500.0
        );
    }

    public function testConstructWithZeroAmountShouldSucceed(): void
    {
        $deduction = new Deduction(
            type: DeductionType::ChildEducation,
            amount: 0.0
        );

        $this->assertEquals(0.0, $deduction->getAmount());
    }

    public function testChildEducationDeductionExceedingLimitShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('扣除类型 子女教育 的金额 2500.00 超出法定上限 2000.00');

        new Deduction(
            type: DeductionType::ChildEducation,
            amount: 2500.0
        );
    }

    public function testContinuingEducationDeductionExceedingLimitShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('扣除类型 继续教育 的金额 500.00 超出法定上限 400.00');

        new Deduction(
            type: DeductionType::ContinuingEducation,
            amount: 500.0
        );
    }

    public function testSeriousIllnessDeductionExceedingLimitShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('扣除类型 大病医疗 的金额 85000.00 超出法定上限 80000.00');

        new Deduction(
            type: DeductionType::SeriousIllness,
            amount: 85000.0
        );
    }

    public function testHousingLoanDeductionExceedingLimitShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('扣除类型 住房贷款利息 的金额 1200.00 超出法定上限 1000.00');

        new Deduction(
            type: DeductionType::HousingLoan,
            amount: 1200.0
        );
    }

    public function testHousingRentDeductionExceedingLimitShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('扣除类型 住房租金 的金额 1600.00 超出法定上限 1500.00');

        new Deduction(
            type: DeductionType::HousingRent,
            amount: 1600.0
        );
    }

    public function testElderCareDeductionExceedingLimitShouldThrowException(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('扣除类型 赡养老人 的金额 3500.00 超出法定上限 3000.00');

        new Deduction(
            type: DeductionType::ElderCare,
            amount: 3500.0
        );
    }

    public function testMaximumValidChildEducationDeductionShouldSucceed(): void
    {
        $deduction = new Deduction(
            type: DeductionType::ChildEducation,
            amount: 2000.0
        );

        $this->assertEquals(2000.0, $deduction->getAmount());
        $this->assertEquals(DeductionType::ChildEducation, $deduction->getType());
    }

    public function testMaximumValidContinuingEducationDeductionShouldSucceed(): void
    {
        $deduction = new Deduction(
            type: DeductionType::ContinuingEducation,
            amount: 400.0,
            description: '学历教育 - 在职研究生'
        );

        $this->assertEquals(400.0, $deduction->getAmount());
        $this->assertEquals(DeductionType::ContinuingEducation, $deduction->getType());
        $this->assertEquals('学历教育 - 在职研究生', $deduction->getDescription());
    }

    public function testMaximumValidSeriousIllnessDeductionShouldSucceed(): void
    {
        $deduction = new Deduction(
            type: DeductionType::SeriousIllness,
            amount: 80000.0,
            description: '大病医疗 - 年度最高扣除'
        );

        $this->assertEquals(80000.0, $deduction->getAmount());
        $this->assertEquals(DeductionType::SeriousIllness, $deduction->getType());
    }

    public function testMaximumValidHousingLoanDeductionShouldSucceed(): void
    {
        $deduction = new Deduction(
            type: DeductionType::HousingLoan,
            amount: 1000.0,
            metadata: ['loan_bank' => '某某银行', 'loan_start_date' => '2024-01-01']
        );

        $this->assertEquals(1000.0, $deduction->getAmount());
        $this->assertEquals(DeductionType::HousingLoan, $deduction->getType());
        $this->assertArrayHasKey('loan_bank', $deduction->getMetadata());
    }

    public function testMaximumValidHousingRentDeductionShouldSucceed(): void
    {
        $deduction = new Deduction(
            type: DeductionType::HousingRent,
            amount: 1500.0,
            description: '住房租金 - 一线城市标准'
        );

        $this->assertEquals(1500.0, $deduction->getAmount());
        $this->assertEquals(DeductionType::HousingRent, $deduction->getType());
    }

    public function testMaximumValidElderCareDeductionShouldSucceed(): void
    {
        $deduction = new Deduction(
            type: DeductionType::ElderCare,
            amount: 3000.0,
            metadata: ['elder_count' => 2, 'sharing_siblings' => false]
        );

        $this->assertEquals(3000.0, $deduction->getAmount());
        $this->assertEquals(DeductionType::ElderCare, $deduction->getType());
        $this->assertEquals(2, $deduction->getMetadata()['elder_count']);
    }

    public function testBoundaryValuesAtExactLimitsShouldSucceed(): void
    {
        $deductionTypes = [
            [DeductionType::ChildEducation, 2000.0],
            [DeductionType::ContinuingEducation, 400.0],
            [DeductionType::SeriousIllness, 80000.0],
            [DeductionType::HousingLoan, 1000.0],
            [DeductionType::HousingRent, 1500.0],
            [DeductionType::ElderCare, 3000.0],
        ];

        foreach ($deductionTypes as [$type, $maxAmount]) {
            $deduction = new Deduction($type, $maxAmount);
            $this->assertEquals($maxAmount, $deduction->getAmount());
            $this->assertEquals($type, $deduction->getType());
        }
    }

    public function testBoundaryValuesExceedingLimitsByOneDecimalShouldThrowException(): void
    {
        $deductionTypes = [
            [DeductionType::ChildEducation, 2000.01],
            [DeductionType::ContinuingEducation, 400.01],
            [DeductionType::SeriousIllness, 80000.01],
            [DeductionType::HousingLoan, 1000.01],
            [DeductionType::HousingRent, 1500.01],
            [DeductionType::ElderCare, 3000.01],
        ];

        foreach ($deductionTypes as [$type, $amount]) {
            try {
                new Deduction($type, $amount);
                self::fail("Expected DataValidationException for {$type->getLabel()} with amount {$amount}");
            } catch (DataValidationException $e) {
                $this->assertStringContainsString('超出法定上限', $e->getMessage());
            }
        }
    }

    public function testImmutabilityOfReadonlyClass(): void
    {
        $originalAmount = 1000.0;
        $originalDescription = '住房贷款利息';
        $originalMetadata = ['bank' => 'ABC银行'];

        $deduction = new Deduction(
            type: DeductionType::HousingLoan,
            amount: $originalAmount,
            description: $originalDescription,
            metadata: $originalMetadata
        );

        // 验证所有属性都可以正确获取且不能修改
        $this->assertEquals(DeductionType::HousingLoan, $deduction->getType());
        $this->assertEquals($originalAmount, $deduction->getAmount());
        $this->assertEquals($originalDescription, $deduction->getDescription());
        $this->assertEquals($originalMetadata, $deduction->getMetadata());

        // 修改获取到的元数据数组不应该影响原对象
        $metadata = $deduction->getMetadata();
        $metadata['new_field'] = 'new_value';
        $this->assertNotEquals($metadata, $deduction->getMetadata());
    }

    public function testDifferentDeductionTypesShouldMaintainTypeIdentity(): void
    {
        $childEducation = new Deduction(DeductionType::ChildEducation, 1000.0);
        $housingLoan = new Deduction(DeductionType::HousingLoan, 1000.0);

        $this->assertEquals(DeductionType::ChildEducation, $childEducation->getType());
        $this->assertEquals(DeductionType::HousingLoan, $housingLoan->getType());
        $this->assertNotEquals($childEducation->getType(), $housingLoan->getType());
    }

    public function testMetadataCanContainComplexData(): void
    {
        $complexMetadata = [
            'child_info' => [
                'name' => '张小明',
                'birth_date' => '2010-05-15',
                'school' => '某某小学',
            ],
            'documents' => ['enrollment_certificate', 'birth_certificate'],
            'calculation_details' => [
                'months_applicable' => 12,
                'monthly_amount' => 2000.0,
                'total_annual' => 24000.0,
            ],
            'approval_status' => true,
            'last_updated' => '2025-01-01',
        ];

        $deduction = new Deduction(
            type: DeductionType::ChildEducation,
            amount: 2000.0,
            description: '子女教育专项扣除',
            metadata: $complexMetadata
        );

        $retrievedMetadata = $deduction->getMetadata();
        $this->assertEquals($complexMetadata, $retrievedMetadata);
        $this->assertIsArray($retrievedMetadata);
        $this->assertArrayHasKey('child_info', $retrievedMetadata);
        $this->assertIsArray($retrievedMetadata['child_info']);
        $this->assertEquals('张小明', $retrievedMetadata['child_info']['name']);
        $this->assertArrayHasKey('documents', $retrievedMetadata);
        $this->assertIsArray($retrievedMetadata['documents']);
        $this->assertCount(2, $retrievedMetadata['documents']);
        $this->assertTrue($retrievedMetadata['approval_status']);
    }

    public function testValidationForFloatingPointPrecision(): void
    {
        // 测试浮点数精度边界情况
        $deduction = new Deduction(
            type: DeductionType::HousingRent,
            amount: 1499.99
        );

        $this->assertEquals(1499.99, $deduction->getAmount());

        // 测试刚好超出限制的情况
        $this->expectException(DataValidationException::class);
        new Deduction(
            type: DeductionType::HousingRent,
            amount: 1500.001
        );
    }
}
