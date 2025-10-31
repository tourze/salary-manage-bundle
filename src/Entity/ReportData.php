<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\SalaryManageBundle\Repository\ReportDataRepository;

/**
 * 报表数据实体
 * 存储生成的报表数据，包括表头、数据和汇总信息
 */
#[ORM\Entity(repositoryClass: ReportDataRepository::class)]
#[ORM\Table(name: 'salary_report_data', options: ['comment' => '报表数据表'])]
class ReportData implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '报表ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '报表类型'])]
    #[Assert\NotBlank(message: '报表类型不能为空')]
    #[Assert\Length(max: 100, maxMessage: '报表类型不能超过100个字符')]
    private string $reportType;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '报表标题'])]
    #[Assert\NotBlank(message: '报表标题不能为空')]
    #[Assert\Length(max: 255, maxMessage: '报表标题不能超过255个字符')]
    private string $title;

    #[ORM\ManyToOne(targetEntity: PayrollPeriod::class)]
    #[ORM\JoinColumn(name: 'period_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: '报表期间不能为空')]
    private PayrollPeriod $period;

    /** @var array<int, string> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '报表表头'])]
    #[Assert\NotNull(message: '报表表头不能为空')]
    private array $headers;

    /** @var array<int, array<string, mixed>> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '报表数据'])]
    #[Assert\NotNull(message: '报表数据不能为空')]
    private array $data;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '汇总信息'])]
    #[Assert\NotNull(message: '汇总信息不能为空')]
    private array $summary;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '元数据'])]
    #[Assert\Type(type: 'array', message: '元数据必须是数组')]
    private array $metadata = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '生成时间'])]
    #[Assert\NotNull(message: '生成时间不能为空')]
    private \DateTimeImmutable $generatedAt;

    public function __construct()
    {
        $this->generatedAt = new \DateTimeImmutable();
        $this->headers = [];
        $this->data = [];
        $this->summary = [];
        $this->metadata = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReportType(): string
    {
        return $this->reportType;
    }

    public function setReportType(string $reportType): void
    {
        $this->reportType = $reportType;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getPeriod(): PayrollPeriod
    {
        return $this->period;
    }

    public function setPeriod(PayrollPeriod $period): void
    {
        $this->period = $period;
    }

    /** @return array<int, string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /** @param array<int, string> $headers */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /** @return array<int, array<string, mixed>> */
    public function getData(): array
    {
        return $this->data;
    }

    /** @param array<int, array<string, mixed>> $data */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /** @return array<string, mixed> */
    public function getSummary(): array
    {
        return $this->summary;
    }

    /** @param array<string, mixed> $summary */
    public function setSummary(array $summary): void
    {
        $this->summary = $summary;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /** @param array<string, mixed> $metadata */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getGeneratedAt(): \DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(\DateTimeImmutable $generatedAt): void
    {
        $this->generatedAt = $generatedAt;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s (%s)', $this->title, $this->reportType, $this->period->getDisplayName());
    }

    public function getTotalRows(): int
    {
        return count($this->data);
    }

    /**
     * 创建新的报表数据实例
     *
     * @param array<int, string> $headers
     * @param array<int, array<string, mixed>> $data
     * @param array<string, mixed> $summary
     * @param array<string, mixed> $metadata
     */
    public static function create(
        string $reportType,
        string $title,
        PayrollPeriod $period,
        array $headers,
        array $data,
        array $summary,
        array $metadata = [],
        ?\DateTimeImmutable $generatedAt = null
    ): self {
        $report = new self();
        $report->reportType = $reportType;
        $report->title = $title;
        $report->period = $period;
        $report->headers = $headers;
        $report->data = $data;
        $report->summary = $summary;
        $report->metadata = $metadata;
        if ($generatedAt !== null) {
            $report->generatedAt = $generatedAt;
        }

        return $report;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'report_type' => $this->reportType,
            'title' => $this->title,
            'period' => $this->period->getDisplayName(),
            'headers' => $this->headers,
            'data' => $this->data,
            'summary' => $this->summary,
            'metadata' => $this->metadata,
            'total_rows' => $this->getTotalRows(),
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
