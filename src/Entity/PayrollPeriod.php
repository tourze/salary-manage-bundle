<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\SalaryManageBundle\Exception\DataValidationException;
use Tourze\SalaryManageBundle\Repository\PayrollPeriodRepository;

/**
 * 薪资期间实体
 * 代表薪资计算的时间周期
 */
#[ORM\Entity(repositoryClass: PayrollPeriodRepository::class)]
#[ORM\Table(name: 'salary_payroll_periods', options: ['comment' => '薪资周期表'])]
class PayrollPeriod implements \Stringable
{
    /** @phpstan-ignore property.unusedType */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '周期ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '年份'])]
    #[Assert\NotNull(message: '年份不能为空')]
    #[Assert\Range(min: 1900, max: 3000, notInRangeMessage: '年份必须在 {{ min }} 到 {{ max }} 之间')]
    private int $year;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '月份'])]
    #[Assert\NotNull(message: '月份不能为空')]
    #[Assert\Range(min: 1, max: 12, notInRangeMessage: '月份必须在 {{ min }} 到 {{ max }} 之间')]
    private int $month;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否已关闭'])]
    #[Assert\Type(type: 'bool', message: '关闭状态必须是布尔值')]
    private bool $isClosed = false;

    public function __construct()
    {
        // 贫血模型：无参数构造函数，属性通过setter设置
    }

    public static function fromDateTime(\DateTimeInterface $date): self
    {
        $period = new self();
        $period->setYear((int) $date->format('Y'));
        $period->setMonth((int) $date->format('n'));

        return $period;
    }

    public static function current(): self
    {
        return self::fromDateTime(new \DateTimeImmutable());
    }

    public function __toString(): string
    {
        return sprintf('%d年%02d月', $this->year, $this->month);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): void
    {
        if ($year < 1900 || $year > 3000) {
            throw new DataValidationException('Year must be between 1900 and 3000');
        }
        $this->year = $year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function setMonth(int $month): void
    {
        if ($month < 1 || $month > 12) {
            throw new DataValidationException('Month must be between 1 and 12');
        }
        $this->month = $month;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    public function setIsClosed(bool $isClosed): void
    {
        $this->isClosed = $isClosed;
    }

    public function getKey(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    public function getDisplayName(): string
    {
        return sprintf('%d年%d月', $this->year, $this->month);
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(sprintf('%04d-%02d-01', $this->year, $this->month));
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->getStartDate()->modify('last day of this month');
    }

    public function equals(PayrollPeriod $other): bool
    {
        return $this->year === $other->year && $this->month === $other->month;
    }

    public function isCurrent(): bool
    {
        $current = self::current();

        return $this->equals($current);
    }

    public function getDaysInMonth(): int
    {
        return (int) $this->getEndDate()->format('j');
    }

    public function getNextPeriod(): self
    {
        $date = $this->getStartDate()->modify('+1 month');

        return self::fromDateTime($date);
    }

    public function getPreviousPeriod(): self
    {
        $date = $this->getStartDate()->modify('-1 month');

        return self::fromDateTime($date);
    }
}
