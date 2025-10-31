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
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Tourze\SalaryManageBundle\Entity\SalaryItem;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;

/**
 * @extends AbstractCrudController<SalaryItem>
 */
#[AdminCrud(
    routePath: '/salary-manage/salary-item',
    routeName: 'salary_manage_salary_item'
)]
final class SalaryItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SalaryItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('工资项目')
            ->setEntityLabelInPlural('工资项目管理')
            ->setPageTitle(Crud::PAGE_INDEX, '工资项目列表')
            ->setPageTitle(Crud::PAGE_NEW, '创建工资项目')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑工资项目')
            ->setPageTitle(Crud::PAGE_DETAIL, '工资项目详情')
            ->setDefaultSort(['type' => 'ASC'])
            ->setSearchFields(['description'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('type', '项目类型')
            ->setRequired(true)
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => SalaryItemType::class,
                'choice_label' => fn (SalaryItemType $type) => $type->getDisplayName(),
            ])
            ->setHelp('选择工资项目类型')
        ;

        yield TextField::new('displayName', '项目名称')
            ->hideOnForm()
            ->setHelp('项目的显示名称')
            ->formatValue(function ($value, SalaryItem $entity) {
                return $entity->getType()->getDisplayName();
            })
        ;

        yield MoneyField::new('amount', '金额')
            ->setRequired(true)
            ->setCurrency('CNY')
            ->setNumDecimals(2)
            ->setHelp('项目的金额（负数表示扣款）')
        ;

        yield TextareaField::new('description', '描述')
            ->setNumOfRows(3)
            ->setHelp('项目的详细描述')
        ;

        yield BooleanField::new('isDeduction', '是否扣款')
            ->hideOnForm()
            ->setHelp('该项目是否为扣款项目')
            ->formatValue(function ($value, SalaryItem $entity) {
                return $entity->isDeduction();
            })
        ;

        if (Crud::PAGE_DETAIL === $pageName || Crud::PAGE_EDIT === $pageName) {
            yield ArrayField::new('metadata', '元数据')
                ->hideOnIndex()
                ->setHelp('项目的额外配置信息')
            ;
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            yield ArrayField::new('itemData', '项目数据')
                ->hideOnForm()
                ->setHelp('完整的项目数据')
                ->formatValue(function ($value, SalaryItem $entity) {
                    return $entity->toArray();
                })
            ;
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        $choices = [];
        foreach (SalaryItemType::cases() as $case) {
            $choices[$case->getDisplayName()] = $case->value;
        }

        return $filters
            ->add(ChoiceFilter::new('type', '项目类型')->setChoices($choices))
            ->add(BooleanFilter::new('isDeduction', '是否扣款'))
        ;
    }
}
