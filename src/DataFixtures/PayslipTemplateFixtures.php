<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\SalaryManageBundle\Entity\PayslipTemplate;

class PayslipTemplateFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $template1 = PayslipTemplate::create(
            'default_template',
            '默认工资条模板',
            '<h1>工资条</h1><p>员工姓名：{{employee_name}}</p><p>发薪期间：{{period}}</p><p>实发工资：{{net_salary}}</p>',
            'html',
            ['company_name' => '公司名称'],
            ['color' => '#000000'],
            true,
            ['version' => '1.0']
        );

        $template2 = PayslipTemplate::create(
            'simple_template',
            '简单工资条模板',
            '员工姓名：{{employee_name}}\n发薪期间：{{period}}\n实发工资：{{net_salary}}',
            'text',
            [],
            [],
            false,
            ['version' => '1.0']
        );

        $template3 = PayslipTemplate::create(
            'detailed_template',
            '详细工资条模板',
            '<div><h2>工资条详情</h2><p>员工：{{employee_name}}</p><p>期间：{{period}}</p><p>基本工资：{{basic_salary}}</p><p>实发工资：{{net_salary}}</p></div>',
            'html',
            ['department' => '部门'],
            ['font-size' => '14px'],
            false,
            ['version' => '2.0']
        );

        $manager->persist($template1);
        $manager->persist($template2);
        $manager->persist($template3);

        $manager->flush();
    }
}
