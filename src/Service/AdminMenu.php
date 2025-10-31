<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Service;

use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\CrudMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SectionMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\SalaryManageBundle\Controller\Admin\EmployeeCrudController;
use Tourze\SalaryManageBundle\Controller\Admin\PayrollPeriodCrudController;
use Tourze\SalaryManageBundle\Controller\Admin\PayslipTemplateCrudController;
use Tourze\SalaryManageBundle\Controller\Admin\ReportDataCrudController;
use Tourze\SalaryManageBundle\Controller\Admin\SalaryCalculationCrudController;
use Tourze\SalaryManageBundle\Controller\Admin\SalaryItemCrudController;

/**
 * 薪资管理模块的管理菜单
 */
#[Autoconfigure(public: true)]
class AdminMenu implements MenuProviderInterface
{
    public function __invoke(ItemInterface $item): void
    {
        // 实现接口要求的方法
        // 在实际应用中，这里会添加菜单项到 $item 中
        // 为了保持向后兼容，这里保留 getMenuItems() 方法
    }

    /**
     * 获取薪资管理模块的菜单项
     * @return array<int, CrudMenuItem|SectionMenuItem>
     */
    public function getMenuItems(): array
    {
        return [
            MenuItem::section('薪资管理', 'fa fa-money-bill-wave')->setPermission('ROLE_ADMIN'),

            // 员工管理
            MenuItem::linkToCrud('员工管理', 'fa fa-users', EmployeeCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            // 薪资计算相关
            MenuItem::linkToCrud('薪资计算', 'fa fa-calculator', SalaryCalculationCrudController::class)
                ->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('薪资项目', 'fa fa-list-ul', SalaryItemCrudController::class)
                ->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('工资期间', 'fa fa-calendar-alt', PayrollPeriodCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            // 模板和报表
            MenuItem::linkToCrud('工资条模板', 'fa fa-file-alt', PayslipTemplateCrudController::class)
                ->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('报表数据', 'fa fa-chart-bar', ReportDataCrudController::class)
                ->setPermission('ROLE_ADMIN'),
        ];
    }
}
