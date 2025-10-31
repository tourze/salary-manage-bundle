<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\SalaryManageBundle\Entity\SalaryItem;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;

class SalaryItemFixtures extends Fixture
{
    public const SALARY_ITEM_1_REFERENCE = 'salary-item-1';
    public const SALARY_ITEM_2_REFERENCE = 'salary-item-2';
    public const SALARY_ITEM_3_REFERENCE = 'salary-item-3';
    public const SALARY_ITEM_4_REFERENCE = 'salary-item-4';
    public const SALARY_ITEM_5_REFERENCE = 'salary-item-5';

    public function load(ObjectManager $manager): void
    {
        // 创建薪资项目数据
        /** @var array<int, array{type: SalaryItemType, amount: float, description: string, metadata: array<string, mixed>}> $items */
        $items = [
            [
                'type' => SalaryItemType::BasicSalary,
                'amount' => 15000.00,
                'description' => '基本薪资',
                'metadata' => [
                    'category' => 'income',
                    'tax_deductible' => false,
                ],
            ],
            [
                'type' => SalaryItemType::Bonus,
                'amount' => 3000.00,
                'description' => '绩效奖金',
                'metadata' => [
                    'category' => 'income',
                    'tax_deductible' => false,
                    'performance_rating' => 'A',
                ],
            ],
            [
                'type' => SalaryItemType::Allowance,
                'amount' => 800.00,
                'description' => '交通津贴',
                'metadata' => [
                    'category' => 'allowance',
                    'tax_deductible' => true,
                    'allowance_type' => 'transportation',
                ],
            ],
            [
                'type' => SalaryItemType::SocialInsurance,
                'amount' => -1500.00,
                'description' => '社保个人部分',
                'metadata' => [
                    'category' => 'deduction',
                    'tax_deductible' => true,
                    'insurance_types' => ['pension', 'medical', 'unemployment'],
                ],
            ],
            [
                'type' => SalaryItemType::IncomeTax,
                'amount' => -1200.00,
                'description' => '个人所得税',
                'metadata' => [
                    'category' => 'deduction',
                    'tax_deductible' => false,
                    'tax_rate' => 0.1,
                ],
            ],
        ];

        foreach ($items as $index => $itemData) {
            $item = new SalaryItem();
            $item->setType($itemData['type']);
            $item->setAmount($itemData['amount']);
            $item->setDescription($itemData['description']);
            $item->setMetadata($itemData['metadata']);

            $manager->persist($item);

            // 设置引用，供其他 fixtures 使用
            if (0 === $index) {
                $this->addReference(self::SALARY_ITEM_1_REFERENCE, $item);
            } elseif (1 === $index) {
                $this->addReference(self::SALARY_ITEM_2_REFERENCE, $item);
            } elseif (2 === $index) {
                $this->addReference(self::SALARY_ITEM_3_REFERENCE, $item);
            } elseif (3 === $index) {
                $this->addReference(self::SALARY_ITEM_4_REFERENCE, $item);
            } elseif (4 === $index) {
                $this->addReference(self::SALARY_ITEM_5_REFERENCE, $item);
            }
        }

        $manager->flush();
    }
}
