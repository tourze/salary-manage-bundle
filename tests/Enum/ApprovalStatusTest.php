<?php

namespace Tourze\SalaryManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SalaryManageBundle\Enum\ApprovalStatus;

/**
 * @internal
 */
#[CoversClass(ApprovalStatus::class)]
class ApprovalStatusTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('pending', ApprovalStatus::Pending->value);
        $this->assertEquals('approved', ApprovalStatus::Approved->value);
        $this->assertEquals('rejected', ApprovalStatus::Rejected->value);
        $this->assertEquals('withdrawn', ApprovalStatus::Withdrawn->value);
    }

    #[TestWith(['pending', '待审批'], 'pending label')]
    #[TestWith(['approved', '已审批'], 'approved label')]
    #[TestWith(['rejected', '已拒绝'], 'rejected label')]
    #[TestWith(['withdrawn', '已撤回'], 'withdrawn label')]
    public function testGetLabel(string $statusValue, string $expectedLabel): void
    {
        $status = ApprovalStatus::from($statusValue);
        $this->assertEquals($expectedLabel, $status->getLabel());
    }

    #[TestWith(['pending', false], 'pending completion')]
    #[TestWith(['approved', true], 'approved completion')]
    #[TestWith(['rejected', true], 'rejected completion')]
    #[TestWith(['withdrawn', true], 'withdrawn completion')]
    public function testIsCompleted(string $statusValue, bool $expectedCompleted): void
    {
        $status = ApprovalStatus::from($statusValue);
        $this->assertEquals($expectedCompleted, $status->isCompleted());
    }

    #[TestWith(['pending', true], 'pending withdraw')]
    #[TestWith(['approved', false], 'approved withdraw')]
    #[TestWith(['rejected', false], 'rejected withdraw')]
    #[TestWith(['withdrawn', false], 'withdrawn withdraw')]
    public function testCanWithdraw(string $statusValue, bool $canWithdraw): void
    {
        $status = ApprovalStatus::from($statusValue);
        $this->assertEquals($canWithdraw, $status->canWithdraw());
    }

    #[TestWith(['pending', 'orange'], 'pending status')]
    #[TestWith(['approved', 'green'], 'approved status')]
    #[TestWith(['rejected', 'red'], 'rejected status')]
    #[TestWith(['withdrawn', 'gray'], 'withdrawn status')]
    public function testGetColor(string $statusValue, string $expectedColor): void
    {
        $status = ApprovalStatus::from($statusValue);
        $this->assertEquals($expectedColor, $status->getColor());
    }

    #[TestWith(['pending', 'clock'], 'pending icon')]
    #[TestWith(['approved', 'check'], 'approved icon')]
    #[TestWith(['rejected', 'times'], 'rejected icon')]
    #[TestWith(['withdrawn', 'undo'], 'withdrawn icon')]
    public function testGetIcon(string $statusValue, string $expectedIcon): void
    {
        $status = ApprovalStatus::from($statusValue);
        $this->assertEquals($expectedIcon, $status->getIcon());
    }

    public function testAllStatusesHaveLabels(): void
    {
        $cases = ApprovalStatus::cases();

        foreach ($cases as $status) {
            $label = $status->getLabel();
            $this->assertNotEmpty($label, "Status {$status->value} should have a non-empty label");
            $this->assertIsString($label);
        }
    }

    public function testAllStatusesHaveColors(): void
    {
        $cases = ApprovalStatus::cases();

        foreach ($cases as $status) {
            $color = $status->getColor();
            $this->assertNotEmpty($color, "Status {$status->value} should have a non-empty color");
            $this->assertIsString($color);
        }
    }

    public function testAllStatusesHaveIcons(): void
    {
        $cases = ApprovalStatus::cases();

        foreach ($cases as $status) {
            $icon = $status->getIcon();
            $this->assertNotEmpty($icon, "Status {$status->value} should have a non-empty icon");
            $this->assertIsString($icon);
        }
    }

    public function testItemableImplementation(): void
    {
        // 测试每个状态都实现了 Itemable 接口
        $cases = ApprovalStatus::cases();

        foreach ($cases as $status) {
            $this->assertIsString($status->getLabel());
            $this->assertEquals($status->getLabel(), $status->getLabel()); // 确保方法存在且可调用
        }
    }

    public function testStatusWorkflow(): void
    {
        // 测试状态流转逻辑
        $pending = ApprovalStatus::Pending;
        $approved = ApprovalStatus::Approved;
        $rejected = ApprovalStatus::Rejected;
        $withdrawn = ApprovalStatus::Withdrawn;

        // 待审批状态可以撤回
        $this->assertTrue($pending->canWithdraw());
        $this->assertFalse($pending->isCompleted());

        // 已审批状态不能撤回，已完成
        $this->assertFalse($approved->canWithdraw());
        $this->assertTrue($approved->isCompleted());

        // 已拒绝状态不能撤回，已完成
        $this->assertFalse($rejected->canWithdraw());
        $this->assertTrue($rejected->isCompleted());

        // 已撤回状态不能再撤回，已完成
        $this->assertFalse($withdrawn->canWithdraw());
        $this->assertTrue($withdrawn->isCompleted());
    }

    public function testEnumCases(): void
    {
        $cases = ApprovalStatus::cases();
        $expectedCases = [
            ApprovalStatus::Pending,
            ApprovalStatus::Approved,
            ApprovalStatus::Rejected,
            ApprovalStatus::Withdrawn,
        ];

        $this->assertCount(4, $cases);
        $this->assertEquals($expectedCases, $cases);
    }

    public function testFromStringValue(): void
    {
        $this->assertEquals(ApprovalStatus::Pending, ApprovalStatus::from('pending'));
        $this->assertEquals(ApprovalStatus::Approved, ApprovalStatus::from('approved'));
        $this->assertEquals(ApprovalStatus::Rejected, ApprovalStatus::from('rejected'));
        $this->assertEquals(ApprovalStatus::Withdrawn, ApprovalStatus::from('withdrawn'));
    }

    public function testTryFromStringValue(): void
    {
        $this->assertEquals(ApprovalStatus::Pending, ApprovalStatus::tryFrom('pending'));
        $this->assertEquals(ApprovalStatus::Approved, ApprovalStatus::tryFrom('approved'));
        $this->assertNull(ApprovalStatus::tryFrom('invalid'));
    }

    public function testStatusComparison(): void
    {
        $pending1 = ApprovalStatus::Pending;
        $pending2 = ApprovalStatus::Pending;
        $approved = ApprovalStatus::Approved;

        $this->assertEquals($pending1, $pending2);
        $this->assertNotEquals($pending1, $approved);
    }

    public function testUniqueValues(): void
    {
        $cases = ApprovalStatus::cases();
        $values = array_map(fn ($case) => $case->value, $cases);

        $this->assertEquals(count($values), count(array_unique($values)), 'All enum values should be unique');
    }

    public function testColorValues(): void
    {
        $validColors = ['orange', 'green', 'red', 'gray'];
        $cases = ApprovalStatus::cases();

        foreach ($cases as $status) {
            $this->assertContains($status->getColor(), $validColors,
                "Status {$status->value} should have a valid color");
        }
    }

    public function testIconValues(): void
    {
        $validIcons = ['clock', 'check', 'times', 'undo'];
        $cases = ApprovalStatus::cases();

        foreach ($cases as $status) {
            $this->assertContains($status->getIcon(), $validIcons,
                "Status {$status->value} should have a valid icon");
        }
    }

    public function testToArray(): void
    {
        foreach (ApprovalStatus::cases() as $status) {
            $array = $status->toArray();

            $this->assertIsArray($array);
            $this->assertArrayHasKey('value', $array);
            $this->assertArrayHasKey('label', $array);
            $this->assertEquals($status->value, $array['value']);
            $this->assertEquals($status->getLabel(), $array['label']);
        }
    }
}
