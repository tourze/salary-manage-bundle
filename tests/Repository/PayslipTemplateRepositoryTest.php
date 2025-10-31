<?php

namespace Tourze\SalaryManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SalaryManageBundle\Entity\PayslipTemplate;
use Tourze\SalaryManageBundle\Repository\PayslipTemplateRepository;

/**
 * PayslipTemplate Repository 测试
 * @internal
 */
#[CoversClass(PayslipTemplateRepository::class)]
#[RunTestsInSeparateProcesses]
class PayslipTemplateRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $template = new PayslipTemplate();
        $template->setTemplateId('TEST_' . uniqid());
        $template->setName('测试薪资条模板');
        $template->setContent('员工姓名: {{employee_name}}, 实发工资: {{net_salary}}');
        $template->setFormat('html');
        $template->setVariables(['custom_var' => '自定义变量']);
        $template->setStyles(['color' => 'blue']);
        $template->setIsDefault(false);
        $template->setMetadata(['version' => '1.0', 'author' => 'test']);

        return $template;
    }

    protected function getRepository(): PayslipTemplateRepository
    {
        $repository = self::getEntityManager()->getRepository(PayslipTemplate::class);
        self::assertInstanceOf(PayslipTemplateRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // 子类可以实现额外的设置逻辑
    }

    public function testSaveAndRemoveMethods(): void
    {
        $repository = $this->getRepository();

        $template = new PayslipTemplate();
        $template->setTemplateId('SAVE_TEST_' . uniqid());
        $template->setName('保存测试模板');
        $template->setContent('{{employee_name}} - {{net_salary}}');
        $template->setFormat('pdf');
        $template->setIsDefault(false);

        $repository->save($template, true);

        $this->assertNotNull($template->getId());

        // 验证能够从数据库中找到保存的实体
        $foundTemplate = $repository->find($template->getId());
        $this->assertNotNull($foundTemplate);
        $this->assertEquals($template->getTemplateId(), $foundTemplate->getTemplateId());
        $this->assertEquals('保存测试模板', $foundTemplate->getName());

        // 测试删除
        $id = $template->getId();
        $repository->remove($template, true);

        // 验证已被删除
        $deletedTemplate = $repository->find($id);
        $this->assertNull($deletedTemplate);
    }

    public function testFindDefaultTemplate(): void
    {
        $repository = $this->getRepository();

        // 创建非默认模板
        $nonDefaultTemplate = new PayslipTemplate();
        $nonDefaultTemplate->setTemplateId('NON_DEFAULT_' . uniqid());
        $nonDefaultTemplate->setName('非默认模板');
        $nonDefaultTemplate->setContent('{{employee_name}}');
        $nonDefaultTemplate->setFormat('html');
        $nonDefaultTemplate->setIsDefault(false);

        // 创建默认模板
        $defaultTemplate = new PayslipTemplate();
        $defaultTemplate->setTemplateId('DEFAULT_' . uniqid());
        $defaultTemplate->setName('默认模板');
        $defaultTemplate->setContent('{{employee_name}} - {{net_salary}}');
        $defaultTemplate->setFormat('html');
        $defaultTemplate->setIsDefault(true);

        $repository->save($nonDefaultTemplate, true);
        $repository->save($defaultTemplate, true);

        // 测试查找默认模板
        $foundDefault = $repository->findDefaultTemplate();

        $this->assertNotNull($foundDefault);
        $this->assertTrue($foundDefault->isDefault());
        $this->assertEquals('默认工资条模板', $foundDefault->getName());
    }

    public function testFindByFormat(): void
    {
        $repository = $this->getRepository();

        // 创建不同格式的模板
        $htmlTemplate = new PayslipTemplate();
        $htmlTemplate->setTemplateId('HTML_' . uniqid());
        $htmlTemplate->setName('HTML模板');
        $htmlTemplate->setContent('<h1>{{employee_name}}</h1>');
        $htmlTemplate->setFormat('html');
        $htmlTemplate->setIsDefault(false);

        $pdfTemplate = new PayslipTemplate();
        $pdfTemplate->setTemplateId('PDF_' . uniqid());
        $pdfTemplate->setName('PDF模板');
        $pdfTemplate->setContent('{{employee_name}} - {{net_salary}}');
        $pdfTemplate->setFormat('pdf');
        $pdfTemplate->setIsDefault(false);

        $textTemplate = new PayslipTemplate();
        $textTemplate->setTemplateId('TEXT_' . uniqid());
        $textTemplate->setName('文本模板');
        $textTemplate->setContent('员工: {{employee_name}}');
        $textTemplate->setFormat('text');
        $textTemplate->setIsDefault(false);

        $repository->save($htmlTemplate, true);
        $repository->save($pdfTemplate, true);
        $repository->save($textTemplate, true);

        // 测试按格式查找
        $htmlTemplates = $repository->findByFormat('html');
        $pdfTemplates = $repository->findByFormat('pdf');
        $textTemplates = $repository->findByFormat('text');

        $this->assertGreaterThanOrEqual(1, count($htmlTemplates));
        $this->assertGreaterThanOrEqual(1, count($pdfTemplates));
        $this->assertGreaterThanOrEqual(1, count($textTemplates));

        // 验证返回的模板格式正确
        foreach ($htmlTemplates as $template) {
            $this->assertEquals('html', $template->getFormat());
        }
        foreach ($pdfTemplates as $template) {
            $this->assertEquals('pdf', $template->getFormat());
        }
        foreach ($textTemplates as $template) {
            $this->assertEquals('text', $template->getFormat());
        }
    }

    public function testFindAllOrderedByName(): void
    {
        $repository = $this->getRepository();

        // 创建多个模板，包括默认模板
        $defaultTemplate = new PayslipTemplate();
        $defaultTemplate->setTemplateId('DEFAULT_ORDER_' . uniqid());
        $defaultTemplate->setName('ZZZ 默认模板');
        $defaultTemplate->setContent('{{employee_name}}');
        $defaultTemplate->setFormat('html');
        $defaultTemplate->setIsDefault(true);

        $template1 = new PayslipTemplate();
        $template1->setTemplateId('ORDER_1_' . uniqid());
        $template1->setName('AAA 第一个模板');
        $template1->setContent('{{employee_name}}');
        $template1->setFormat('html');
        $template1->setIsDefault(false);

        $template2 = new PayslipTemplate();
        $template2->setTemplateId('ORDER_2_' . uniqid());
        $template2->setName('BBB 第二个模板');
        $template2->setContent('{{employee_name}}');
        $template2->setFormat('html');
        $template2->setIsDefault(false);

        $repository->save($template1, true);
        $repository->save($template2, true);
        $repository->save($defaultTemplate, true);

        // 测试排序查找
        $orderedTemplates = $repository->findAllOrderedByName();

        $this->assertGreaterThanOrEqual(3, count($orderedTemplates));

        // 验证默认模板排在第一位
        $firstTemplate = $orderedTemplates[0];
        $this->assertTrue($firstTemplate->isDefault());

        // 验证非默认模板按名称排序
        $nonDefaultTemplates = array_values(array_filter($orderedTemplates, fn (PayslipTemplate $t) => !$t->isDefault()));
        if (count($nonDefaultTemplates) >= 2) {
            $names = array_map(fn (PayslipTemplate $t) => $t->getName(), $nonDefaultTemplates);
            $sortedNames = $names;
            sort($sortedNames);
            $this->assertEquals($sortedNames, $names, '非默认模板应该按名称排序');
        }
    }

    public function testTemplateWithComplexData(): void
    {
        $repository = $this->getRepository();

        // 创建包含复杂数据的模板
        $complexTemplate = new PayslipTemplate();
        $complexTemplate->setTemplateId('COMPLEX_' . uniqid());
        $complexTemplate->setName('复杂数据模板');
        $complexTemplate->setContent('
            <div>
                <h1>{{employee_name}}</h1>
                <p>部门: {{department}}</p>
                <p>期间: {{period}}</p>
                <p>实发工资: {{net_salary}}</p>
            </div>
        ');
        $complexTemplate->setFormat('html');
        $complexTemplate->setVariables([
            'company_name' => '公司名称',
            'logo_url' => '企业Logo地址',
            'signature' => '签名信息',
        ]);
        $complexTemplate->setStyles([
            'font-family' => 'Arial, sans-serif',
            'font-size' => '14px',
            'color' => '#333333',
            'background-color' => '#ffffff',
        ]);
        $complexTemplate->setIsDefault(false);
        $complexTemplate->setMetadata([
            'version' => '2.1',
            'author' => '系统管理员',
            'created_date' => '2025-01-01',
            'last_modified' => '2025-01-15',
            'tags' => ['salary', 'template', 'html'],
        ]);

        $repository->save($complexTemplate, true);

        // 验证保存成功
        $this->assertNotNull($complexTemplate->getId());

        // 重新查找并验证数据完整性
        $foundTemplate = $repository->find($complexTemplate->getId());
        $this->assertNotNull($foundTemplate);

        // 验证基本属性
        $this->assertEquals('复杂数据模板', $foundTemplate->getName());
        $this->assertEquals('html', $foundTemplate->getFormat());
        $this->assertFalse($foundTemplate->isDefault());

        // 验证复杂数据结构
        $variables = $foundTemplate->getVariables();
        $this->assertArrayHasKey('company_name', $variables);
        $this->assertEquals('公司名称', $variables['company_name']);

        $styles = $foundTemplate->getStyles();
        $this->assertArrayHasKey('font-family', $styles);
        $this->assertEquals('Arial, sans-serif', $styles['font-family']);

        $metadata = $foundTemplate->getMetadata();
        $this->assertArrayHasKey('version', $metadata);
        $this->assertEquals('2.1', $metadata['version']);
        $this->assertArrayHasKey('tags', $metadata);
        $this->assertIsArray($metadata['tags']);
        $this->assertContains('template', $metadata['tags']);
    }

    public function testFindNonExistentDefaultTemplate(): void
    {
        $repository = $this->getRepository();

        // 确保没有默认模板的情况下测试
        $allTemplates = $repository->findAll();
        foreach ($allTemplates as $template) {
            if ($template->isDefault()) {
                $template->setIsDefault(false);
                $repository->save($template, true);
            }
        }

        // 测试查找默认模板应该返回null
        $defaultTemplate = $repository->findDefaultTemplate();
        $this->assertNull($defaultTemplate);
    }

    public function testRemove(): void
    {
        $repository = $this->getRepository();

        // 创建模板
        $template = new PayslipTemplate();
        $template->setTemplateId('REMOVE_TEST_' . uniqid());
        $template->setName('待删除模板');
        $template->setContent('{{employee_name}}');
        $template->setFormat('text');
        $template->setIsDefault(false);

        $repository->save($template, true);
        $id = $template->getId();

        // 删除模板
        $repository->remove($template, true);

        // 验证已被删除
        $deletedTemplate = $repository->find($id);
        $this->assertNull($deletedTemplate);
    }
}
