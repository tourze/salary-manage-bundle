<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;
use Tourze\SalaryManageBundle\Repository\SalaryItemRepository;

/**
 * 薪资项目
 * 代表一个具体的薪资计算项目
 */
#[ORM\Entity(repositoryClass: SalaryItemRepository::class)]
#[ORM\Table(name: 'salary_items', options: ['comment' => '薪资项目表'])]
class SalaryItem implements \Stringable
{
    /** @phpstan-ignore property.unusedType */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SalaryCalculation::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '关联薪资计算ID'])]
    private ?SalaryCalculation $salaryCalculation = null;

    #[ORM\Column(type: Types::STRING, enumType: SalaryItemType::class, options: ['comment' => '薪资项目类型'])]
    #[Assert\NotNull(message: '薪资项目类型不能为空')]
    #[Assert\Choice(callback: [SalaryItemType::class, 'cases'], message: '薪资项目类型必须是有效值')]
    private SalaryItemType $type;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '金额'])]
    #[Assert\NotNull(message: '金额不能为空')]
    private string $amount;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '描述'])]
    #[Assert\Length(max: 255, maxMessage: '描述不能超过255个字符')]
    private string $description = '';

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '元数据'])]
    #[Assert\Type(type: 'array', message: '元数据必须是数组类型')]
    private array $metadata = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        // 贫血模型：无参数构造函数，属性通过setter设置
        $this->metadata = [];
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s: %.2f', $this->getDescription(), $this->getAmount());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSalaryCalculation(): ?SalaryCalculation
    {
        return $this->salaryCalculation;
    }

    public function setSalaryCalculation(?SalaryCalculation $salaryCalculation): void
    {
        $this->salaryCalculation = $salaryCalculation;
    }

    public function getType(): SalaryItemType
    {
        return $this->type;
    }

    public function setType(SalaryItemType $type): void
    {
        $this->type = $type;
    }

    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = (string) $amount;
    }

    public function getDescription(): string
    {
        return '' !== $this->description ? $this->description : $this->type->getDisplayName();
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDisplayName(): string
    {
        return $this->type->getDisplayName();
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isDeduction(): bool
    {
        return (float) $this->amount < 0 || $this->type->isDeduction();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_name' => $this->type->getDisplayName(),
            'amount' => $this->getAmount(),
            'description' => $this->getDescription(),
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
