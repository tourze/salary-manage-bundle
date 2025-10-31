<?php

namespace Tourze\SalaryManageBundle\Tests\Bundle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\SalaryManageBundle\SalaryManageBundle;

/**
 * Bundle集成测试
 * @internal
 */
#[CoversClass(SalaryManageBundle::class)]
#[RunTestsInSeparateProcesses]
class SalaryManageBundleTest extends AbstractBundleTestCase
{
}
