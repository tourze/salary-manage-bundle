<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;

/**
 * @extends AbstractCrudController<PayrollPeriod>
 */
#[AdminCrud(
    routePath: '/salary-manage/payroll-period',
    routeName: 'salary_manage_payroll_period'
)]
final class PayrollPeriodCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PayrollPeriod::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('薪资期间')
            ->setEntityLabelInPlural('薪资期间管理')
            ->setPageTitle(Crud::PAGE_INDEX, '薪资期间列表')
            ->setPageTitle(Crud::PAGE_NEW, '创建薪资期间')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑薪资期间')
            ->setPageTitle(Crud::PAGE_DETAIL, '薪资期间详情')
            ->setDefaultSort(['year' => 'DESC', 'month' => 'DESC'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield NumberField::new('year', '年份')
            ->setRequired(true)
            ->setHelp('薪资期间的年份')
        ;

        yield NumberField::new('month', '月份')
            ->setRequired(true)
            ->setHelp('薪资期间的月份（1-12）')
        ;

        yield TextField::new('key', '期间标识')
            ->hideOnForm()
            ->setHelp('格式化的期间标识（YYYY-MM）')
            ->formatValue(function ($value, PayrollPeriod $entity) {
                return $entity->getKey();
            })
        ;

        yield TextField::new('displayName', '显示名称')
            ->hideOnForm()
            ->setHelp('中文显示名称')
            ->formatValue(function ($value, PayrollPeriod $entity) {
                return $entity->getDisplayName();
            })
        ;

        yield DateTimeField::new('startDate', '开始日期')
            ->hideOnForm()
            ->setHelp('期间的第一天')
            ->formatValue(function ($value, PayrollPeriod $entity) {
                return $entity->getStartDate()->format('Y-m-d');
            })
        ;

        yield DateTimeField::new('endDate', '结束日期')
            ->hideOnForm()
            ->setHelp('期间的最后一天')
            ->formatValue(function ($value, PayrollPeriod $entity) {
                return $entity->getEndDate()->format('Y-m-d');
            })
        ;

        yield NumberField::new('daysInMonth', '天数')
            ->hideOnForm()
            ->setHelp('本月的总天数')
            ->formatValue(function ($value, PayrollPeriod $entity) {
                return $entity->getDaysInMonth();
            })
        ;

        yield BooleanField::new('current', '当前期间')
            ->hideOnForm()
            ->setHelp('是否为当前月份')
            ->formatValue(function ($value, PayrollPeriod $entity) {
                return $entity->isCurrent();
            })
        ;

        if (Crud::PAGE_DETAIL === $pageName) {
            yield TextField::new('nextPeriodDisplay', '下一期间')
                ->hideOnForm()
                ->setHelp('下一个月的期间')
                ->formatValue(function ($value, PayrollPeriod $entity) {
                    return $entity->getNextPeriod()->getDisplayName();
                })
            ;

            yield TextField::new('previousPeriodDisplay', '上一期间')
                ->hideOnForm()
                ->setHelp('上一个月的期间')
                ->formatValue(function ($value, PayrollPeriod $entity) {
                    return $entity->getPreviousPeriod()->getDisplayName();
                })
            ;
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('year', '年份'))
            ->add(NumericFilter::new('month', '月份'))
        ;
    }
}
