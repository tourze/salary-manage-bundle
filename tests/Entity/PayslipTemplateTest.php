<?php

namespace Tourze\SalaryManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SalaryManageBundle\Entity\PayslipTemplate;
use Tourze\SalaryManageBundle\Exception\DataValidationException;

/**
 * @internal
 */
#[CoversClass(PayslipTemplate::class)]
final class PayslipTemplateTest extends AbstractEntityTestCase
{
    private const TEMPLATE_ID = 'template_001';
    private const TEMPLATE_NAME = '标准薪资条模板';
    private const TEMPLATE_CONTENT = '员工：{{employee_name}} 期间：{{period}} 实发工资：{{net_salary}}';
    private const TEMPLATE_FORMAT = 'html';

    protected function createEntity(): object
    {
        return PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            self::TEMPLATE_CONTENT,
            self::TEMPLATE_FORMAT
        );
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        // PayslipTemplate uses factory method, provide properties with both getter and setter
        return [
            ['templateId', 'tpl_001'],
            ['name', '薪资条模板'],
            ['content', '内容模板'],
            ['format', 'html'],
            ['variables', ['bonus' => '奖金']],
            ['styles', ['font-size' => '12px']],
            ['metadata', ['version' => '1.0']],
        ];
    }

    public function testCreateWithValidParameters(): void
    {
        $variables = ['bonus' => '奖金'];
        $styles = ['font-size' => '12px'];
        $metadata = ['version' => '1.0'];

        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            self::TEMPLATE_CONTENT,
            self::TEMPLATE_FORMAT,
            $variables,
            $styles,
            true,
            $metadata
        );

        $this->assertEquals(self::TEMPLATE_ID, $template->getTemplateId());
        $this->assertEquals(self::TEMPLATE_NAME, $template->getName());
        $this->assertEquals(self::TEMPLATE_CONTENT, $template->getContent());
        $this->assertEquals(self::TEMPLATE_FORMAT, $template->getFormat());
        $this->assertEquals($variables, $template->getVariables());
        $this->assertEquals($styles, $template->getStyles());
        $this->assertTrue($template->isDefault());
        $this->assertEquals($metadata, $template->getMetadata());
    }

    public function testConstructorWithoutParameters(): void
    {
        $template = new PayslipTemplate();

        $this->assertEquals('', $template->getTemplateId());
        $this->assertEquals('', $template->getName());
        $this->assertEquals('', $template->getContent());
        $this->assertEquals('html', $template->getFormat());
        $this->assertEquals([], $template->getVariables());
        $this->assertEquals([], $template->getStyles());
        $this->assertFalse($template->isDefault());
        $this->assertEquals([], $template->getMetadata());
    }

    public function testCreateWithMinimalParameters(): void
    {
        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            self::TEMPLATE_CONTENT,
            self::TEMPLATE_FORMAT
        );

        $this->assertEquals(self::TEMPLATE_ID, $template->getTemplateId());
        $this->assertEquals(self::TEMPLATE_NAME, $template->getName());
        $this->assertEquals(self::TEMPLATE_CONTENT, $template->getContent());
        $this->assertEquals(self::TEMPLATE_FORMAT, $template->getFormat());
        $this->assertEquals([], $template->getVariables());
        $this->assertEquals([], $template->getStyles());
        $this->assertFalse($template->isDefault());
        $this->assertEquals([], $template->getMetadata());
    }

    public function testCreateThrowsExceptionForEmptyContent(): void
    {
        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('薪资条模板内容不能为空');

        PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            '', // 空内容
            self::TEMPLATE_FORMAT
        );
    }

    public function testRenderWithCompleteData(): void
    {
        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            '姓名：{{employee_name}} | 部门：{{department}} | 实发：{{net_salary}}元',
            self::TEMPLATE_FORMAT
        );

        $data = [
            'employee_name' => '李四',
            'department' => '财务部',
            'net_salary' => '8500.00',
        ];

        $result = $template->render($data);

        $this->assertEquals('姓名：李四 | 部门：财务部 | 实发：8500.00元', $result);
    }

    public function testRenderWithMissingData(): void
    {
        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            '姓名：{{employee_name}} | 部门：{{department}} | 实发：{{net_salary}}元',
            self::TEMPLATE_FORMAT
        );

        $data = [
            'employee_name' => '王五',
            // department 缺失
            'net_salary' => '9500.00',
        ];

        $result = $template->render($data);

        // 缺失的变量会保持原样
        $this->assertEquals('姓名：王五 | 部门：{{department}} | 实发：9500.00元', $result);
    }

    public function testRenderWithNumericValues(): void
    {
        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            '基本工资：{{basic_salary}} | 工龄：{{years}}年',
            self::TEMPLATE_FORMAT
        );

        $data = [
            'basic_salary' => 12000,
            'years' => 5,
        ];

        $result = $template->render($data);

        $this->assertEquals('基本工资：12000 | 工龄：5年', $result);
    }

    public function testGetSupportedVariables(): void
    {
        $customVariables = [
            'bonus' => '绩效奖金',
            'commission' => '提成',
        ];

        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            self::TEMPLATE_CONTENT,
            self::TEMPLATE_FORMAT,
            $customVariables
        );

        $supportedVariables = $template->getSupportedVariables();

        // 验证包含基础变量
        $this->assertArrayHasKey('employee_name', $supportedVariables);
        $this->assertArrayHasKey('employee_number', $supportedVariables);
        $this->assertArrayHasKey('net_salary', $supportedVariables);

        // 验证包含自定义变量
        $this->assertArrayHasKey('bonus', $supportedVariables);
        $this->assertArrayHasKey('commission', $supportedVariables);
        $this->assertEquals('绩效奖金', $supportedVariables['bonus']);
        $this->assertEquals('提成', $supportedVariables['commission']);
    }

    public function testValidateTemplateWithValidContent(): void
    {
        $validContent = '{{employee_name}} - {{period}} - {{net_salary}}';

        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            $validContent,
            'html'
        );

        $errors = $template->validateTemplate();

        $this->assertEmpty($errors);
    }

    public function testValidateTemplateWithMissingRequiredVariables(): void
    {
        $invalidContent = '{{employee_name}} - {{basic_salary}}'; // 缺少 period 和 net_salary

        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            $invalidContent,
            'html'
        );

        $errors = $template->validateTemplate();

        $this->assertCount(2, $errors);
        $this->assertContains('缺少必需变量: period', $errors);
        $this->assertContains('缺少必需变量: net_salary', $errors);
    }

    #[TestWith(['html', false])]
    #[TestWith(['pdf', false])]
    #[TestWith(['text', false])]
    #[TestWith(['word', true])]
    #[TestWith(['excel', true])]
    #[TestWith(['', true])]
    public function testValidateTemplateWithDifferentFormats(string $format, bool $shouldHaveError): void
    {
        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            self::TEMPLATE_CONTENT,
            $format
        );

        $errors = $template->validateTemplate();

        if ($shouldHaveError) {
            $this->assertNotEmpty($errors);
            $this->assertStringContainsString('不支持的格式', $errors[0]);
        } else {
            // 只检查格式相关的错误，忽略其他可能的错误
            $formatErrors = array_filter($errors, fn ($error) => false !== strpos($error, '不支持的格式'));
            $this->assertEmpty($formatErrors);
        }
    }

    public function testGetPreviewWithDefaultData(): void
    {
        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            '员工：{{employee_name}} | 部门：{{department}} | 实发：{{net_salary}}',
            self::TEMPLATE_FORMAT
        );

        $preview = $template->getPreview();

        $this->assertStringContainsString('员工：张三', $preview);
        $this->assertStringContainsString('部门：技术部', $preview);
        $this->assertStringContainsString('实发：9,219.00', $preview);
    }

    public function testGetPreviewWithCustomSampleData(): void
    {
        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            '员工：{{employee_name}} | 部门：{{department}} | 实发：{{net_salary}}',
            self::TEMPLATE_FORMAT
        );

        $customData = [
            'employee_name' => '自定义姓名',
            'department' => '自定义部门',
            'net_salary' => '15,000.00',
        ];

        $preview = $template->getPreview($customData);

        $this->assertStringContainsString('员工：自定义姓名', $preview);
        $this->assertStringContainsString('部门：自定义部门', $preview);
        $this->assertStringContainsString('实发：15,000.00', $preview);
    }

    public function testGetPreviewPartialCustomData(): void
    {
        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            '员工：{{employee_name}} | 部门：{{department}} | 实发：{{net_salary}}',
            self::TEMPLATE_FORMAT
        );

        $partialData = [
            'employee_name' => '部分自定义',
            // department 使用默认值
            // net_salary 使用默认值
        ];

        $preview = $template->getPreview($partialData);

        $this->assertStringContainsString('员工：部分自定义', $preview);
        $this->assertStringContainsString('部门：技术部', $preview); // 默认值
        $this->assertStringContainsString('实发：9,219.00', $preview); // 默认值
    }

    public function testComplexTemplateRendering(): void
    {
        $complexContent = <<<'HTML'
            <div class="payslip">
                <h1>{{employee_name}}的薪资条</h1>
                <p>期间：{{period}}</p>
                <table>
                    <tr><td>基本工资</td><td>{{basic_salary}}</td></tr>
                    <tr><td>加班费</td><td>{{overtime_pay}}</td></tr>
                    <tr><td>应发合计</td><td>{{gross_salary}}</td></tr>
                    <tr><td>个人所得税</td><td>{{income_tax}}</td></tr>
                    <tr><td>实发工资</td><td>{{net_salary}}</td></tr>
                </table>
            </div>
            HTML;

        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            '复杂模板',
            $complexContent,
            'html'
        );

        $data = [
            'employee_name' => '复杂测试',
            'period' => '2025-01',
            'basic_salary' => '15000',
            'overtime_pay' => '2000',
            'gross_salary' => '17000',
            'income_tax' => '1200',
            'net_salary' => '15800',
        ];

        $result = $template->render($data);

        $this->assertStringContainsString('<h1>复杂测试的薪资条</h1>', $result);
        $this->assertStringContainsString('<td>15000</td>', $result);
        $this->assertStringContainsString('<td>15800</td>', $result);
    }

    public function testTemplateWithSpecialCharacters(): void
    {
        $template = PayslipTemplate::create(
            self::TEMPLATE_ID,
            self::TEMPLATE_NAME,
            '员工：{{employee_name}} & 公司：{{company}} | 税前：￥{{gross_salary}}',
            self::TEMPLATE_FORMAT
        );

        $data = [
            'employee_name' => 'John & Jane',
            'company' => 'ABC公司',
            'gross_salary' => '20,000.00',
        ];

        $result = $template->render($data);

        $this->assertEquals('员工：John & Jane & 公司：ABC公司 | 税前：￥20,000.00', $result);
    }
}
