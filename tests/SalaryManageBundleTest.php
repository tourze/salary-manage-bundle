<?php

declare(strict_types=1);

namespace SalaryManageBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\SalaryManageBundle\SalaryManageBundle;

/**
 * @internal
 */
#[CoversClass(SalaryManageBundle::class)]
#[RunTestsInSeparateProcesses]
final class SalaryManageBundleTest extends AbstractBundleTestCase
{
}
