<?php

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SalaryManageBundle\Entity\SalaryItem;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;

/**
 * @internal
 */
#[CoversClass(SalaryItem::class)]
final class SalaryItemTest extends AbstractEntityTestCase
{
    /**
     * 创建被测实体的一个实例.
     */
    protected function createEntity(): object
    {
        $item = new SalaryItem();
        $item->setType(SalaryItemType::BasicSalary);
        $item->setAmount(15000.0);
        $item->setDescription('月度基本工资');
        $item->setMetadata(['department' => '技术部']);

        return $item;
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'type' => ['type', SalaryItemType::BasicSalary],
            'amount' => ['amount', 15000.0],
            'description' => ['description', '月度基本工资'],
            'metadata' => ['metadata', ['department' => '技术部']],
        ];
    }

    public function testConstructorWithAllParameters(): void
    {
        $type = SalaryItemType::BasicSalary;
        $amount = 15000.0;
        $description = '月度基本工资';
        $metadata = ['department' => '技术部', 'grade' => 'P6'];

        $item = new SalaryItem();
        $item->setType($type);
        $item->setAmount($amount);
        $item->setDescription($description);
        $item->setMetadata($metadata);

        $this->assertEquals($type, $item->getType());
        $this->assertEquals($amount, $item->getAmount());
        $this->assertEquals($description, $item->getDescription());
        $this->assertEquals($metadata, $item->getMetadata());
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $type = SalaryItemType::Bonus;
        $amount = 5000.0;

        $item = new SalaryItem();
        $item->setType($type);
        $item->setAmount($amount);

        $this->assertEquals($type, $item->getType());
        $this->assertEquals($amount, $item->getAmount());
        $this->assertEquals($type->getDisplayName(), $item->getDescription()); // 默认使用类型显示名称
        $this->assertEquals([], $item->getMetadata());
    }

    public function testGetDescriptionWithEmptyDescription(): void
    {
        $type = SalaryItemType::PerformanceBonus;
        $item = new SalaryItem();
        $item->setType($type);
        $item->setAmount(3000.0);
        $item->setDescription('');

        $this->assertEquals($type->getDisplayName(), $item->getDescription());
    }

    public function testGetDescriptionWithCustomDescription(): void
    {
        $type = SalaryItemType::Allowance;
        $customDescription = '特殊岗位津贴';
        $item = new SalaryItem();
        $item->setType($type);
        $item->setAmount(800.0);
        $item->setDescription($customDescription);

        $this->assertEquals($customDescription, $item->getDescription());
    }

    #[TestWith(['social_insurance', 1000.0, true])]
    #[TestWith(['social_insurance', -500.0, true])]
    #[TestWith(['income_tax', 800.0, true])]
    #[TestWith(['basic_salary', 10000.0, false])]
    #[TestWith(['basic_salary', -100.0, true])]
    #[TestWith(['bonus', 2000.0, false])]
    #[TestWith(['bonus', 0.0, false])]
    #[TestWith(['allowance', -50.0, true])]
    public function testIsDeduction(string $typeValue, float $amount, bool $expectedIsDeduction): void
    {
        $type = SalaryItemType::from($typeValue);
        $item = new SalaryItem();
        $item->setType($type);
        $item->setAmount($amount);
        $this->assertEquals($expectedIsDeduction, $item->isDeduction());
    }

    public function testToArray(): void
    {
        $type = SalaryItemType::Overtime;
        $amount = 1500.0;
        $description = '周末加班费';
        $metadata = ['hours' => 20, 'rate' => 75.0];

        $item = new SalaryItem();
        $item->setType($type);
        $item->setAmount($amount);
        $item->setDescription($description);
        $item->setMetadata($metadata);

        $result = $item->toArray();

        // 验证核心字段存在且正确
        $this->assertEquals($type->value, $result['type']);
        $this->assertEquals($type->getDisplayName(), $result['type_name']);
        $this->assertEquals($amount, $result['amount']);
        $this->assertEquals($description, $result['description']);
        $this->assertEquals($metadata, $result['metadata']);

        // 验证新增字段存在
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertNull($result['id']); // 新创建的实体ID为null
        $this->assertIsString($result['created_at']); // 创建时间应该是字符串格式
    }

    public function testToArrayWithEmptyDescription(): void
    {
        $type = SalaryItemType::Commission;
        $amount = 2500.0;

        $item = new SalaryItem();
        $item->setType($type);
        $item->setAmount($amount);

        $result = $item->toArray();

        $this->assertEquals($type->value, $result['type']);
        $this->assertEquals($type->getDisplayName(), $result['type_name']);
        $this->assertEquals($type->getDisplayName(), $result['description']); // 使用类型显示名称
        $this->assertEquals($amount, $result['amount']);
        $this->assertEquals([], $result['metadata']);
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
    public function testWithDifferentSalaryItemTypes(string $typeValue, string $expectedDisplayName): void
    {
        $type = SalaryItemType::from($typeValue);
        $item = new SalaryItem();
        $item->setType($type);
        $item->setAmount(1000.0);
        $this->assertEquals($type, $item->getType());
        $this->assertEquals($expectedDisplayName, $item->getDescription());
        $this->assertEquals($expectedDisplayName, $item->toArray()['type_name']);
    }

    public function testZeroAmountItem(): void
    {
        $item = new SalaryItem();
        $item->setType(SalaryItemType::Bonus);
        $item->setAmount(0.0);
        $item->setDescription('无奖金');

        $this->assertEquals(0.0, $item->getAmount());
        $this->assertFalse($item->isDeduction());
        $this->assertEquals('无奖金', $item->getDescription());
    }

    public function testNegativeAmountItem(): void
    {
        $item = new SalaryItem();
        $item->setType(SalaryItemType::BasicSalary);
        $item->setAmount(-100.0);
        $item->setDescription('迟到扣款');

        $this->assertEquals(-100.0, $item->getAmount());
        $this->assertTrue($item->isDeduction()); // 负数金额为扣减
        $this->assertEquals('迟到扣款', $item->getDescription());
    }

    public function testLargeAmountItem(): void
    {
        $largeAmount = 999999.99;
        $item = new SalaryItem();
        $item->setType(SalaryItemType::SpecialReward);
        $item->setAmount($largeAmount);
        $item->setDescription('年终特殊奖励');

        $this->assertEquals($largeAmount, $item->getAmount());
        $this->assertFalse($item->isDeduction());
    }

    public function testComplexMetadata(): void
    {
        $complexMetadata = [
            'calculation_rule' => 'performance_based',
            'multiplier' => 1.5,
            'base_value' => 10000,
            'criteria' => ['kpi_score' => 95, 'attendance' => 100],
            'approval_info' => [
                'approved_by' => 'manager_001',
                'approved_at' => '2025-01-15',
            ],
        ];

        $item = new SalaryItem();
        $item->setType(SalaryItemType::PerformanceBonus);
        $item->setAmount(15000.0);
        $item->setDescription('年度绩效奖金');
        $item->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $item->getMetadata());

        $arrayResult = $item->toArray();
        $this->assertEquals($complexMetadata, $arrayResult['metadata']);
    }

    public function testReadOnlyNature(): void
    {
        $item = new SalaryItem();
        $item->setType(SalaryItemType::Allowance);
        $item->setAmount(500.0);

        // 验证所有属性都有对应的 getter 方法
        $this->assertInstanceOf(SalaryItemType::class, $item->getType());
        $this->assertIsFloat($item->getAmount());
        $this->assertIsString($item->getDescription());
        $this->assertIsArray($item->getMetadata());
        $this->assertIsBool($item->isDeduction());
        $this->assertIsArray($item->toArray());
    }

    public function testMultipleItemsWithSameType(): void
    {
        $item1 = new SalaryItem();
        $item1->setType(SalaryItemType::Allowance);
        $item1->setAmount(500.0);
        $item1->setDescription('交通补助');
        $item2 = new SalaryItem();
        $item2->setType(SalaryItemType::Allowance);
        $item2->setAmount(300.0);
        $item2->setDescription('通讯补助');
        $item3 = new SalaryItem();
        $item3->setType(SalaryItemType::Allowance);
        $item3->setAmount(200.0);
        $item3->setDescription('餐饮补助');

        $this->assertEquals(SalaryItemType::Allowance, $item1->getType());
        $this->assertEquals(SalaryItemType::Allowance, $item2->getType());
        $this->assertEquals(SalaryItemType::Allowance, $item3->getType());

        $this->assertEquals('交通补助', $item1->getDescription());
        $this->assertEquals('通讯补助', $item2->getDescription());
        $this->assertEquals('餐饮补助', $item3->getDescription());

        $this->assertEquals(500.0, $item1->getAmount());
        $this->assertEquals(300.0, $item2->getAmount());
        $this->assertEquals(200.0, $item3->getAmount());
    }
}
