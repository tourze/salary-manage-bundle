<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\SalaryManageBundle\Entity\Employee;

/**
 * @extends AbstractCrudController<Employee>
 */
#[AdminCrud(
    routePath: '/salary-manage/employee',
    routeName: 'salary_manage_employee'
)]
final class EmployeeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Employee::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('员工')
            ->setEntityLabelInPlural('员工管理')
            ->setPageTitle(Crud::PAGE_INDEX, '员工列表')
            ->setPageTitle(Crud::PAGE_NEW, '创建员工')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑员工')
            ->setPageTitle(Crud::PAGE_DETAIL, '员工详情')
            ->setDefaultSort(['hireDate' => 'DESC'])
            ->setSearchFields(['employeeNumber', 'name', 'department', 'idNumber'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield TextField::new('employeeNumber', '员工编号')
            ->setRequired(true)
            ->setHelp('员工的唯一编号')
        ;

        yield TextField::new('name', '姓名')
            ->setRequired(true)
            ->setHelp('员工姓名')
        ;

        yield TextField::new('department', '部门')
            ->setHelp('员工所属部门')
        ;

        yield MoneyField::new('baseSalary', '基本薪资')
            ->setRequired(true)
            ->setCurrency('CNY')
            ->setNumDecimals(2)
            ->setHelp('员工的基本月薪')
        ;

        yield DateField::new('hireDate', '入职日期')
            ->setRequired(true)
            ->setHelp('员工入职日期')
        ;

        yield TextField::new('idNumber', '身份证号码')
            ->setHelp('员工身份证号码')
        ;

        if (Crud::PAGE_DETAIL === $pageName || Crud::PAGE_EDIT === $pageName) {
            yield ArrayField::new('specialDeductions', '专项附加扣除配置')
                ->hideOnIndex()
                ->setHelp('员工的专项附加扣除配置')
            ;
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('department', '部门'))
            ->add(DateTimeFilter::new('hireDate', '入职日期'))
        ;
    }
}
