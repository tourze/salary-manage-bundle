<?php

namespace Tourze\SalaryManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SalaryManageBundle\Entity\SalaryItem;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;
use Tourze\SalaryManageBundle\Repository\SalaryItemRepository;

/**
 * SalaryItem Repository 测试
 * @internal
 */
#[CoversClass(SalaryItemRepository::class)]
#[RunTestsInSeparateProcesses]
class SalaryItemRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $item = new SalaryItem();
        $item->setType(SalaryItemType::BasicSalary);
        $item->setAmount(5000.00);
        $item->setDescription('基本工资');

        return $item;
    }

    protected function getRepository(): SalaryItemRepository
    {
        $repository = self::getEntityManager()->getRepository(SalaryItem::class);
        self::assertInstanceOf(SalaryItemRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // 子类可以实现额外的设置逻辑
    }

    public function testSaveAndRemoveMethods(): void
    {
        $repository = $this->getRepository();

        $item = new SalaryItem();
        $item->setType(SalaryItemType::Bonus);
        $item->setAmount(1000.00);
        $item->setDescription('绩效奖金');

        $repository->save($item, true);

        $this->assertNotNull($item->getId());

        // 验证能够从数据库中找到保存的实体
        $foundItem = $repository->find($item->getId());
        $this->assertNotNull($foundItem);
        $this->assertEquals($item->getDescription(), $foundItem->getDescription());

        // 测试删除
        $id = $item->getId();
        $repository->remove($item, true);

        // 验证已被删除
        $deletedItem = $repository->find($id);
        $this->assertNull($deletedItem);
    }

    public function testCreateDifferentTypes(): void
    {
        $repository = $this->getRepository();

        // 创建不同类型的薪资项目
        $types = [
            ['type' => SalaryItemType::BasicSalary, 'amount' => 5000.00, 'description' => '基本工资'],
            ['type' => SalaryItemType::Bonus, 'amount' => 1000.00, 'description' => '绩效奖金'],
            ['type' => SalaryItemType::Allowance, 'amount' => 500.00, 'description' => '交通补贴'],
            ['type' => SalaryItemType::IncomeTax, 'amount' => 200.00, 'description' => '个人所得税'],
        ];

        foreach ($types as $typeData) {
            $item = new SalaryItem();
            $item->setType($typeData['type']);
            $item->setAmount($typeData['amount']);
            $item->setDescription($typeData['description']);

            $repository->save($item, true);

            $this->assertNotNull($item->getId());
            $this->assertEquals($typeData['type'], $item->getType());
        }
    }

    public function testMetadata(): void
    {
        $repository = $this->getRepository();

        $item = new SalaryItem();
        $item->setType(SalaryItemType::BasicSalary);
        $item->setAmount(6000.00);
        $item->setDescription('带元数据的基本工资');
        $item->setMetadata([
            'department' => '技术部',
            'level' => 'P6',
            'calculated_by' => 'system',
        ]);

        $repository->save($item, true);

        $this->assertNotNull($item->getId());

        // 从数据库重新读取并验证元数据
        $foundItem = $repository->find($item->getId());
        $this->assertNotNull($foundItem);

        $metadata = $foundItem->getMetadata();
        $this->assertIsArray($metadata);
        $this->assertEquals('技术部', $metadata['department']);
        $this->assertEquals('P6', $metadata['level']);
    }
}