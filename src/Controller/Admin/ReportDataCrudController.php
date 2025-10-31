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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\SalaryManageBundle\Entity\ReportData;

/**
 * @extends AbstractCrudController<ReportData>
 */
#[AdminCrud(
    routePath: '/salary-manage/report-data',
    routeName: 'salary_manage_report_data'
)]
final class ReportDataCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ReportData::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('报表数据')
            ->setEntityLabelInPlural('报表数据管理')
            ->setPageTitle(Crud::PAGE_INDEX, '报表数据列表')
            ->setPageTitle(Crud::PAGE_NEW, '创建报表数据')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑报表数据')
            ->setPageTitle(Crud::PAGE_DETAIL, '报表数据详情')
            ->setDefaultSort(['generatedAt' => 'DESC'])
            ->setSearchFields(['reportType', 'title'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('reportType', '报表类型')
            ->setRequired(true)
            ->setHelp('报表的类型标识')
        ;

        yield TextField::new('title', '报表标题')
            ->setRequired(true)
            ->setHelp('报表的显示标题')
        ;

        yield AssociationField::new('period', '报表期间')
            ->setRequired(true)
            ->setHelp('报表对应的薪资期间')
        ;

        yield DateTimeField::new('generatedAt', '创建时间')
            ->setRequired(true)
            ->setHelp('报表的创建时间')
        ;

        yield NumberField::new('totalRows', '数据行数')
            ->hideOnForm()
            ->setHelp('报表包含的数据行数')
            ->formatValue(function ($value, ReportData $entity) {
                return $entity->getTotalRows();
            })
        ;

        if (Crud::PAGE_DETAIL === $pageName || Crud::PAGE_EDIT === $pageName) {
            yield ArrayField::new('headers', '表头')
                ->setRequired(true)
                ->setHelp('报表的列标题')
            ;

            yield ArrayField::new('data', '报表数据')
                ->setRequired(true)
                ->hideOnIndex()
                ->setHelp('报表的具体数据内容')
            ;

            yield ArrayField::new('summary', '汇总信息')
                ->setRequired(true)
                ->hideOnIndex()
                ->setHelp('报表的汇总统计信息')
            ;

            yield ArrayField::new('metadata', '元数据')
                ->hideOnIndex()
                ->setHelp('报表的额外配置信息')
            ;
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            yield ArrayField::new('fullData', '完整数据')
                ->hideOnForm()
                ->setHelp('报表的完整数组格式数据')
                ->formatValue(function ($value, ReportData $entity) {
                    return $entity->toArray();
                })
            ;
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('reportType', '报表类型'))
            ->add(DateTimeFilter::new('generatedAt', '生成时间'))
        ;
    }
}
