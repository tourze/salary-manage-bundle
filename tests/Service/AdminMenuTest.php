<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\SalaryManageBundle\Service\AdminMenu;

/**
 * AdminMenu 服务的集成测试
 *
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    protected function onSetUp(): void
    {
        // 集成测试环境设置，这里暂时不需要特殊配置
    }

    private function getAdminMenuService(): AdminMenu
    {
        return self::getService(AdminMenu::class);
    }

    /**
     * 测试获取菜单项
     */
    public function testGetMenuItems(): void
    {
        $this->adminMenu = $this->getAdminMenuService();
        $menuItems = $this->adminMenu->getMenuItems();
        $this->assertNotEmpty($menuItems);

        // 验证所有项目都是 MenuItemInterface 实例
        foreach ($menuItems as $index => $item) {
            $this->assertNotNull($item, sprintf('Item %d is null', $index));
        }

        // 验证菜单项数量（1个section + 6个CRUD控制器）
        $this->assertCount(7, $menuItems, '应该包含7个菜单项（1个section + 6个CRUD控制器）');
    }

    /**
     * 测试菜单项的结构
     */
    public function testMenuItemsStructure(): void
    {
        $this->adminMenu = $this->getAdminMenuService();
        $menuItems = $this->adminMenu->getMenuItems();

        // 验证菜单项数量
        $this->assertCount(7, $menuItems);

        // 验证菜单项都是 MenuItemInterface 实例
        foreach ($menuItems as $index => $item) {
            $this->assertNotNull($item, sprintf('Item %d is null', $index));
        }
    }

    /**
     * 测试所有CRUD控制器都有对应的菜单项
     */
    public function testAllCrudControllersHaveMenuItems(): void
    {
        $this->adminMenu = $this->getAdminMenuService();
        $menuItems = $this->adminMenu->getMenuItems();

        // 期望的控制器类名（只包含实际存在的控制器）
        $expectedControllers = [
            'EmployeeCrudController',
            'SalaryCalculationCrudController',
            'SalaryItemCrudController',
            'PayrollPeriodCrudController',
            'PayslipTemplateCrudController',
            'ReportDataCrudController',
        ];

        // 验证每个控制器都有对应的菜单项
        $this->assertCount(count($expectedControllers) + 1, $menuItems,
            '菜单项数量应该等于控制器数量加1个section');
    }
}
