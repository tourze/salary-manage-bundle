<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\SalaryManageBundle\Enum\SalaryItemType;
use Tourze\SalaryManageBundle\Repository\SalaryCalculationRepository;

/**
 * 薪资计算结果 - 聚合根
 * 包含完整的薪资计算信息
 */
#[ORM\Entity(repositoryClass: SalaryCalculationRepository::class)]
#[ORM\Table(name: 'salary_calculations', options: ['comment' => '薪资计算结果表'])]
class SalaryCalculation implements \Stringable
{
    /** @phpstan-ignore property.unusedType */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '关联员工ID'])]
    #[Assert\NotNull(message: '员工不能为空')]
    private Employee $employee;

    #[ORM\ManyToOne(targetEntity: PayrollPeriod::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '关联薪资期间ID'])]
    #[Assert\NotNull(message: '薪资期间不能为空')]
    private PayrollPeriod $period;

    /**
     * @var Collection<int, SalaryItem>
     */
    #[ORM\OneToMany(targetEntity: SalaryItem::class, mappedBy: 'salaryCalculation', cascade: ['persist', 'remove'])]
    private Collection $items;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '计算上下文数据'])]
    #[Assert\Type(type: 'array', message: '上下文数据必须是数组类型')]
    private array $context = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        // 贫血模型：无参数构造函数，属性通过setter设置
        $this->items = new ArrayCollection();
        $this->context = [];
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s (%.2f)',
            isset($this->employee) ? $this->employee->getName() : 'Unknown',
            isset($this->period) ? $this->period->getDisplayName() : 'Unknown Period',
            $this->getNetAmount()
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getPeriod(): PayrollPeriod
    {
        return $this->period;
    }

    public function setPeriod(PayrollPeriod $period): void
    {
        $this->period = $period;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function addItem(SalaryItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setSalaryCalculation($this);
        }

        return $this;
    }

    public function removeItem(SalaryItem $item): self
    {
        if ($this->items->removeElement($item)) {
            $item->setSalaryCalculation(null);
        }

        return $this;
    }

    public function getTotalByItemType(string|SalaryItemType $type): float
    {
        $items = $this->getItemsByType($type);

        return array_reduce(
            $items,
            fn (float $total, SalaryItem $item) => $total + $item->getAmount(),
            0.0
        );
    }

    /**
     * @return Collection<int, SalaryItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /** @return array<int, SalaryItem> */
    public function getItemsByType(string|SalaryItemType $type): array
    {
        $typeValue = $type instanceof SalaryItemType ? $type->value : $type;

        return array_values($this->items->filter(fn (SalaryItem $item) => $item->getType()->value === $typeValue)->toArray());
    }

    public function getGrossAmount(): float
    {
        return array_reduce(
            $this->items->toArray(),
            fn (float $total, SalaryItem $item) => $total + ($item->isDeduction() ? 0 : $item->getAmount()),
            0.0
        );
    }

    public function getDeductionsAmount(): float
    {
        return array_reduce(
            $this->items->toArray(),
            fn (float $total, SalaryItem $item) => $total + ($item->isDeduction() ? abs($item->getAmount()) : 0),
            0.0
        );
    }

    public function getNetAmount(): float
    {
        return $this->getGrossAmount() - $this->getDeductionsAmount();
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->context;
    }

    public function setContextValue(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    public function getItemsCount(): int
    {
        return $this->items->count();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee->getId(),
            'employee_number' => $this->employee->getEmployeeNumber(),
            'period' => $this->period->getKey(),
            'gross_amount' => $this->getGrossAmount(),
            'deductions_amount' => $this->getDeductionsAmount(),
            'net_amount' => $this->getNetAmount(),
            'items' => array_map(fn (SalaryItem $item) => $item->toArray(), $this->items->toArray()),
            'context' => $this->context,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
