<?php

namespace Tourze\SalaryManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SalaryManageBundle\Enum\PaymentStatus;

/**
 * @internal
 */
#[CoversClass(PaymentStatus::class)]
class PaymentStatusTest extends AbstractEnumTestCase
{
    public function testAllPaymentStatuses(): void
    {
        $expectedStatuses = [
            'pending',
            'processing',
            'success',
            'failed',
            'cancelled',
            'refunded',
        ];

        $actualStatuses = array_map(fn (PaymentStatus $status) => $status->value, PaymentStatus::cases());

        $this->assertCount(6, PaymentStatus::cases());
        $this->assertEquals($expectedStatuses, $actualStatuses);
    }

    public function testGetLabelForPending(): void
    {
        $this->assertEquals('待处理', PaymentStatus::Pending->getLabel());
    }

    public function testGetLabelForProcessing(): void
    {
        $this->assertEquals('处理中', PaymentStatus::Processing->getLabel());
    }

    public function testGetLabelForSuccess(): void
    {
        $this->assertEquals('成功', PaymentStatus::Success->getLabel());
    }

    public function testGetLabelForFailed(): void
    {
        $this->assertEquals('失败', PaymentStatus::Failed->getLabel());
    }

    public function testGetLabelForCancelled(): void
    {
        $this->assertEquals('已取消', PaymentStatus::Cancelled->getLabel());
    }

    public function testGetLabelForRefunded(): void
    {
        $this->assertEquals('已退款', PaymentStatus::Refunded->getLabel());
    }

    public function testIsCompletedForSuccess(): void
    {
        $this->assertTrue(PaymentStatus::Success->isCompleted());
    }

    public function testIsCompletedForFailed(): void
    {
        $this->assertTrue(PaymentStatus::Failed->isCompleted());
    }

    public function testIsCompletedForCancelled(): void
    {
        $this->assertTrue(PaymentStatus::Cancelled->isCompleted());
    }

    public function testIsCompletedForRefunded(): void
    {
        $this->assertTrue(PaymentStatus::Refunded->isCompleted());
    }

    public function testIsCompletedForPending(): void
    {
        $this->assertFalse(PaymentStatus::Pending->isCompleted());
    }

    public function testIsCompletedForProcessing(): void
    {
        $this->assertFalse(PaymentStatus::Processing->isCompleted());
    }

    public function testCanCancelForPending(): void
    {
        $this->assertTrue(PaymentStatus::Pending->canCancel());
    }

    public function testCanCancelForProcessing(): void
    {
        $this->assertTrue(PaymentStatus::Processing->canCancel());
    }

    public function testCanCancelForSuccess(): void
    {
        $this->assertFalse(PaymentStatus::Success->canCancel());
    }

    public function testCanCancelForFailed(): void
    {
        $this->assertFalse(PaymentStatus::Failed->canCancel());
    }

    public function testCanCancelForCancelled(): void
    {
        $this->assertFalse(PaymentStatus::Cancelled->canCancel());
    }

    public function testCanCancelForRefunded(): void
    {
        $this->assertFalse(PaymentStatus::Refunded->canCancel());
    }

    public function testCanRetryForFailed(): void
    {
        $this->assertTrue(PaymentStatus::Failed->canRetry());
    }

    public function testCanRetryForPending(): void
    {
        $this->assertFalse(PaymentStatus::Pending->canRetry());
    }

    public function testCanRetryForProcessing(): void
    {
        $this->assertFalse(PaymentStatus::Processing->canRetry());
    }

    public function testCanRetryForSuccess(): void
    {
        $this->assertFalse(PaymentStatus::Success->canRetry());
    }

    public function testCanRetryForCancelled(): void
    {
        $this->assertFalse(PaymentStatus::Cancelled->canRetry());
    }

    public function testCanRetryForRefunded(): void
    {
        $this->assertFalse(PaymentStatus::Refunded->canRetry());
    }

    public function testGetColorForPending(): void
    {
        $this->assertEquals('orange', PaymentStatus::Pending->getColor());
    }

    public function testGetColorForProcessing(): void
    {
        $this->assertEquals('blue', PaymentStatus::Processing->getColor());
    }

    public function testGetColorForSuccess(): void
    {
        $this->assertEquals('green', PaymentStatus::Success->getColor());
    }

    public function testGetColorForFailed(): void
    {
        $this->assertEquals('red', PaymentStatus::Failed->getColor());
    }

    public function testGetColorForCancelled(): void
    {
        $this->assertEquals('gray', PaymentStatus::Cancelled->getColor());
    }

    public function testGetColorForRefunded(): void
    {
        $this->assertEquals('purple', PaymentStatus::Refunded->getColor());
    }

    public function testEnumValues(): void
    {
        $this->assertEquals('pending', PaymentStatus::Pending->value);
        $this->assertEquals('processing', PaymentStatus::Processing->value);
        $this->assertEquals('success', PaymentStatus::Success->value);
        $this->assertEquals('failed', PaymentStatus::Failed->value);
        $this->assertEquals('cancelled', PaymentStatus::Cancelled->value);
        $this->assertEquals('refunded', PaymentStatus::Refunded->value);
    }

    public function testImplementsItemable(): void
    {
        $this->assertContains('Tourze\EnumExtra\Itemable', class_implements(PaymentStatus::class));
    }

    public function testImplementsLabelable(): void
    {
        $this->assertContains('Tourze\EnumExtra\Labelable', class_implements(PaymentStatus::class));
    }

    public function testImplementsSelectable(): void
    {
        $this->assertContains('Tourze\EnumExtra\Selectable', class_implements(PaymentStatus::class));
    }

    public function testUsesItemTrait(): void
    {
        $this->assertContains('Tourze\EnumExtra\ItemTrait', class_uses(PaymentStatus::class));
    }

    public function testUsesSelectTrait(): void
    {
        $this->assertContains('Tourze\EnumExtra\SelectTrait', class_uses(PaymentStatus::class));
    }

    public function testFromMethodWithValidValue(): void
    {
        $this->assertEquals(PaymentStatus::Pending, PaymentStatus::from('pending'));
        $this->assertEquals(PaymentStatus::Processing, PaymentStatus::from('processing'));
        $this->assertEquals(PaymentStatus::Success, PaymentStatus::from('success'));
        $this->assertEquals(PaymentStatus::Failed, PaymentStatus::from('failed'));
        $this->assertEquals(PaymentStatus::Cancelled, PaymentStatus::from('cancelled'));
        $this->assertEquals(PaymentStatus::Refunded, PaymentStatus::from('refunded'));
    }

    public function testTryFromMethodWithValidValue(): void
    {
        $this->assertEquals(PaymentStatus::Pending, PaymentStatus::tryFrom('pending'));
        $this->assertEquals(PaymentStatus::Success, PaymentStatus::tryFrom('success'));
        $this->assertNull(PaymentStatus::tryFrom('invalid_status'));
    }

    public function testStatusTransitionLogic(): void
    {
        // 测试状态转换逻辑的一致性
        foreach (PaymentStatus::cases() as $status) {
            // 已完成的状态不能取消
            if ($status->isCompleted()) {
                $this->assertFalse($status->canCancel(), "Completed status {$status->value} should not be cancellable");
            }

            // 只有失败状态可以重试
            if (PaymentStatus::Failed !== $status) {
                $this->assertFalse($status->canRetry(), "Only failed status should be retryable, not {$status->value}");
            }
        }
    }

    public function testColorMapping(): void
    {
        $expectedColors = ['orange', 'blue', 'green', 'red', 'gray', 'purple'];
        $actualColors = [];

        foreach (PaymentStatus::cases() as $status) {
            $color = $status->getColor();
            $this->assertIsString($color);
            $this->assertNotEmpty($color);
            $actualColors[] = $color;
        }

        // 验证所有颜色都不同且在预期范围内
        $this->assertCount(6, array_unique($actualColors), 'All statuses should have different colors');

        foreach ($actualColors as $color) {
            $this->assertContains($color, $expectedColors, "Color {$color} is not in expected color list");
        }
    }

    public function testStatusGroups(): void
    {
        $activeStatuses = [];
        $completedStatuses = [];
        $cancellableStatuses = [];
        $retryableStatuses = [];

        foreach (PaymentStatus::cases() as $status) {
            if (!$status->isCompleted()) {
                $activeStatuses[] = $status;
            } else {
                $completedStatuses[] = $status;
            }

            if ($status->canCancel()) {
                $cancellableStatuses[] = $status;
            }

            if ($status->canRetry()) {
                $retryableStatuses[] = $status;
            }
        }

        $this->assertCount(2, $activeStatuses, 'Should have 2 active statuses (pending, processing)');
        $this->assertCount(4, $completedStatuses, 'Should have 4 completed statuses (success, failed, cancelled, refunded)');
        $this->assertCount(2, $cancellableStatuses, 'Should have 2 cancellable statuses (pending, processing)');
        $this->assertCount(1, $retryableStatuses, 'Should have 1 retryable status (failed)');

        $this->assertContains(PaymentStatus::Pending, $activeStatuses);
        $this->assertContains(PaymentStatus::Processing, $activeStatuses);
        $this->assertContains(PaymentStatus::Failed, $retryableStatuses);
    }

    public function testStatusCharacteristics(): void
    {
        foreach (PaymentStatus::cases() as $status) {
            // 验证每个状态都有明确的特征
            $this->assertIsString($status->getLabel());
            $this->assertIsBool($status->isCompleted());
            $this->assertIsBool($status->canCancel());
            $this->assertIsBool($status->canRetry());
            $this->assertIsString($status->getColor());

            // 验证标签和颜色不为空
            $this->assertNotEmpty($status->getLabel());
            $this->assertNotEmpty($status->getColor());
        }
    }

    public function testToArray(): void
    {
        foreach (PaymentStatus::cases() as $status) {
            $array = $status->toArray();

            $this->assertIsArray($array);
            $this->assertArrayHasKey('value', $array);
            $this->assertArrayHasKey('label', $array);
            $this->assertEquals($status->value, $array['value']);
            $this->assertEquals($status->getLabel(), $array['label']);
        }
    }
}
