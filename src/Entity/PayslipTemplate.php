<?php

namespace Tourze\SalaryManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\SalaryManageBundle\Exception\DataValidationException;
use Tourze\SalaryManageBundle\Repository\PayslipTemplateRepository;

/**
 * 薪资条模板实体 - 贫血模型设计
 * 用于管理薪资条的模板格式和内容
 */
#[ORM\Entity(repositoryClass: PayslipTemplateRepository::class)]
#[ORM\Table(name: 'payslip_template', options: ['comment' => '薪资条模板表'])]
class PayslipTemplate implements \Stringable
{
    /** @phpstan-ignore property.unusedType */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '模板ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true, options: ['comment' => '模板唯一标识'])]
    #[Assert\NotBlank(message: '模板标识不能为空')]
    #[Assert\Length(max: 255, maxMessage: '模板标识不能超过255个字符')]
    private string $templateId;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '模板名称'])]
    #[Assert\NotBlank(message: '模板名称不能为空')]
    #[Assert\Length(max: 255, maxMessage: '模板名称不能超过255个字符')]
    private string $name;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '模板内容'])]
    #[Assert\NotBlank(message: '模板内容不能为空')]
    private string $content;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '模板格式(html/pdf/text)'])]
    #[Assert\NotBlank(message: '模板格式不能为空')]
    #[Assert\Choice(choices: ['html', 'pdf', 'text'], message: '模板格式必须是 html、pdf 或 text 之一')]
    private string $format;

    /**
     * @var array<string, string>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '模板变量配置'])]
    #[Assert\Type(type: 'array', message: '变量配置必须是数组类型')]
    private array $variables = [];

    /**
     * @var array<string, string>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '模板样式配置'])]
    #[Assert\Type(type: 'array', message: '样式配置必须是数组类型')]
    private array $styles = [];

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否默认模板'])]
    #[Assert\Type(type: 'bool', message: '默认模板标识必须是布尔类型')]
    private bool $isDefault = false;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '模板元数据'])]
    #[Assert\Type(type: 'array', message: '元数据必须是数组类型')]
    private array $metadata = [];

    /**
     * ORM/EasyAdmin 兼容性的默认构造函数
     */
    public function __construct()
    {
        // Initialize with safe defaults for EasyAdmin compatibility
        $this->templateId = '';
        $this->name = '';
        $this->content = '';
        $this->format = 'html';
        $this->variables = [];
        $this->styles = [];
        $this->isDefault = false;
        $this->metadata = [];
    }

    /**
     * 创建验证过的实例的静态工厂方法
     * @param array<string, string> $variables
     * @param array<string, string> $styles
     * @param array<string, mixed> $metadata
     */
    public static function create(
        string $templateId,
        string $name,
        string $content,
        string $format,
        array $variables = [],
        array $styles = [],
        bool $isDefault = false,
        array $metadata = [],
    ): self {
        if ('' === $content || '0' === $content) {
            throw new DataValidationException('薪资条模板内容不能为空');
        }

        $instance = new self();
        $instance->templateId = $templateId;
        $instance->name = $name;
        $instance->content = $content;
        $instance->format = $format;
        $instance->variables = $variables;
        $instance->styles = $styles;
        $instance->isDefault = $isDefault;
        $instance->metadata = $metadata;

        return $instance;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->name, $this->templateId);
    }

    public function setTemplateId(string $templateId): void
    {
        $this->templateId = $templateId;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    /**
     * @param array<string, string> $variables
     */
    public function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }

    /**
     * @param array<string, string> $styles
     */
    public function setStyles(array $styles): void
    {
        $this->styles = $styles;
    }

    public function setIsDefault(bool $isDefault): void
    {
        $this->isDefault = $isDefault;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getTemplateId(): string
    {
        return $this->templateId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @return array<string, string>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @return array<string, string>
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, string|int|float> $data
     */
    public function render(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, (string) $value, $content);
        }

        return $content;
    }

    /**
     * @return array<string, string>
     */
    public function getSupportedVariables(): array
    {
        $requiredVariables = [
            'employee_name' => '员工姓名',
            'employee_number' => '员工编号',
            'department' => '部门',
            'period' => '发薪期间',
            'basic_salary' => '基本工资',
            'overtime_pay' => '加班费',
            'allowances' => '津贴补助',
            'gross_salary' => '应发工资',
            'pension_insurance' => '养老保险',
            'medical_insurance' => '医疗保险',
            'unemployment_insurance' => '失业保险',
            'housing_fund' => '住房公积金',
            'total_social_insurance' => '五险一金小计',
            'income_tax' => '个人所得税',
            'total_deductions' => '扣款小计',
            'net_salary' => '实发工资',
        ];

        return array_merge($requiredVariables, $this->variables);
    }

    /**
     * @return array<int, string>
     */
    public function validateTemplate(): array
    {
        $errors = [];
        $content = $this->content;

        // 检查必需变量
        $requiredVars = ['employee_name', 'period', 'net_salary'];
        foreach ($requiredVars as $var) {
            if (false === strpos($content, '{{' . $var . '}}')) {
                $errors[] = "缺少必需变量: {$var}";
            }
        }

        // 检查格式
        if (!in_array($this->format, ['html', 'pdf', 'text'], true)) {
            $errors[] = "不支持的格式: {$this->format}";
        }

        return $errors;
    }

    /**
     * @param array<string, string> $sampleData
     */
    public function getPreview(array $sampleData = []): string
    {
        $defaultData = [
            'employee_name' => '张三',
            'employee_number' => 'E001',
            'department' => '技术部',
            'period' => '2025年1月',
            'basic_salary' => '10,000.00',
            'overtime_pay' => '1,500.00',
            'allowances' => '500.00',
            'gross_salary' => '12,000.00',
            'pension_insurance' => '960.00',
            'medical_insurance' => '240.00',
            'unemployment_insurance' => '36.00',
            'housing_fund' => '1,200.00',
            'total_social_insurance' => '2,436.00',
            'income_tax' => '345.00',
            'total_deductions' => '2,781.00',
            'net_salary' => '9,219.00',
        ];

        $data = array_merge($defaultData, $sampleData);

        return $this->render($data);
    }
}
