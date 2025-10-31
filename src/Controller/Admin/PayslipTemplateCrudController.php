<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Tourze\SalaryManageBundle\Entity\PayslipTemplate;

/**
 * @extends AbstractCrudController<PayslipTemplate>
 */
#[AdminCrud(
    routePath: '/salary-manage/payslip-template',
    routeName: 'salary_manage_payslip_template'
)]
final class PayslipTemplateCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PayslipTemplate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('工资条模板')
            ->setEntityLabelInPlural('工资条模板管理')
            ->setPageTitle(Crud::PAGE_INDEX, '工资条模板列表')
            ->setPageTitle(Crud::PAGE_NEW, '创建工资条模板')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑工资条模板')
            ->setPageTitle(Crud::PAGE_DETAIL, '工资条模板详情')
            ->setDefaultSort(['isDefault' => 'DESC', 'name' => 'ASC'])
            ->setSearchFields(['templateId', 'name', 'format'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('templateId', '模板ID')
            ->setRequired(true)
            ->setHelp('模板的唯一标识符')
        ;

        yield TextField::new('name', '模板名称')
            ->setRequired(true)
            ->setHelp('模板的显示名称')
        ;

        yield ChoiceField::new('format', '模板格式')
            ->setRequired(true)
            ->setChoices([
                'HTML' => 'html',
                'PDF' => 'pdf',
                '纯文本' => 'text',
            ])
            ->setHelp('选择模板输出格式')
        ;

        yield BooleanField::new('isDefault', '默认模板')
            ->setHelp('是否为系统默认模板')
        ;

        if (Crud::PAGE_EDIT === $pageName || Crud::PAGE_NEW === $pageName) {
            yield CodeEditorField::new('content', '模板内容')
                ->setRequired(true)
                ->setLanguage('javascript')
                ->setNumOfRows(15)
                ->setHelp('工资条模板的HTML内容，使用{{变量名}}占位符')
            ;
        } else {
            yield TextareaField::new('content', '模板内容')
                ->setNumOfRows(5)
                ->hideOnIndex()
            ;
        }

        if (Crud::PAGE_DETAIL === $pageName || Crud::PAGE_EDIT === $pageName) {
            yield ArrayField::new('variables', '自定义变量')
                ->hideOnIndex()
                ->setHelp('模板中使用的自定义变量定义')
            ;

            yield ArrayField::new('styles', '样式配置')
                ->hideOnIndex()
                ->setHelp('模板的CSS样式配置')
            ;

            yield ArrayField::new('metadata', '元数据')
                ->hideOnIndex()
                ->setHelp('模板的额外配置信息')
            ;
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            yield ArrayField::new('supportedVariables', '支持的变量')
                ->hideOnForm()
                ->setHelp('模板支持的所有变量列表')
                ->formatValue(function ($value, PayslipTemplate $entity) {
                    return $entity->getSupportedVariables();
                })
            ;

            yield TextareaField::new('preview', '预览效果')
                ->hideOnForm()
                ->setNumOfRows(10)
                ->setHelp('使用示例数据生成的预览效果')
                ->formatValue(function ($value, PayslipTemplate $entity) {
                    return $entity->getPreview();
                })
            ;

            yield ArrayField::new('validationErrors', '验证错误')
                ->hideOnForm()
                ->setHelp('模板验证发现的问题')
                ->formatValue(function ($value, PayslipTemplate $entity) {
                    return $entity->validateTemplate();
                })
            ;
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('format', '模板格式')->setChoices([
                'HTML' => 'html',
                'PDF' => 'pdf',
                '纯文本' => 'text',
            ]))
            ->add(BooleanFilter::new('isDefault', '默认模板'))
        ;
    }
}
