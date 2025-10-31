<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;

/**
 * @extends AbstractCrudController<SalaryCalculation>
 */
#[AdminCrud(
    routePath: '/salary-manage/salary-calculation',
    routeName: 'salary_manage_salary_calculation'
)]
final class SalaryCalculationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SalaryCalculation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('工资计算')
            ->setEntityLabelInPlural('工资计算管理')
            ->setPageTitle(Crud::PAGE_INDEX, '工资计算列表')
            ->setPageTitle(Crud::PAGE_NEW, '创建工资计算')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑工资计算')
            ->setPageTitle(Crud::PAGE_DETAIL, '工资计算详情')
            ->setSearchFields([])
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('employee', '员工')
            ->setRequired(true)
            ->setHelp('工资计算对应的员工')
        ;

        yield AssociationField::new('period', '计算期间')
            ->setRequired(true)
            ->setHelp('工资计算对应的薪资期间')
        ;

        yield MoneyField::new('grossAmount', '应发工资')
            ->setCurrency('CNY')
            ->setNumDecimals(2)
            ->hideOnForm()
            ->setHelp('扣除前的总工资')
            ->formatValue(function ($value, SalaryCalculation $entity) {
                return $entity->getGrossAmount();
            })
        ;

        yield MoneyField::new('deductionsAmount', '扣款总额')
            ->setCurrency('CNY')
            ->setNumDecimals(2)
            ->hideOnForm()
            ->setHelp('所有扣款项目的总和')
            ->formatValue(function ($value, SalaryCalculation $entity) {
                return $entity->getDeductionsAmount();
            })
        ;

        yield MoneyField::new('netAmount', '实发工资')
            ->setCurrency('CNY')
            ->setNumDecimals(2)
            ->hideOnForm()
            ->setHelp('扣除后的实际发放金额')
            ->formatValue(function ($value, SalaryCalculation $entity) {
                return $entity->getNetAmount();
            })
        ;

        yield NumberField::new('itemsCount', '项目数量')
            ->hideOnForm()
            ->setHelp('工资计算包含的项目总数')
        ;

        if (Crud::PAGE_DETAIL === $pageName || Crud::PAGE_EDIT === $pageName) {
            yield AssociationField::new('items', '工资项目')
                ->setHelp('工资计算包含的所有项目')
            ;

            yield ArrayField::new('context', '计算上下文')
                ->hideOnIndex()
                ->setHelp('工资计算的上下文信息')
            ;
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            yield ArrayField::new('calculationData', '计算详情')
                ->hideOnForm()
                ->setHelp('完整的工资计算数据')
                ->formatValue(function ($value, SalaryCalculation $entity) {
                    return $entity->toArray();
                })
            ;
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters;
    }
}
