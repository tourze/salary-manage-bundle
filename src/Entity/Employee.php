<?php

namespace Tourze\SalaryManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\SalaryManageBundle\Repository\EmployeeRepository;

/**
 * 员工实体 - 贫血模型设计
 * 只包含数据和getter/setter方法，无业务逻辑
 */
#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'salary_employees', options: ['comment' => '薪资系统员工表'])]
class Employee implements \Stringable
{
    /** @phpstan-ignore property.unusedType */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '员工ID'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true, options: ['comment' => '员工编号'])]
    #[Assert\NotBlank(message: '员工编号不能为空')]
    #[Assert\Length(max: 50, maxMessage: '员工编号不能超过50个字符')]
    private string $employeeNumber;

    #[ORM\Column(length: 100, options: ['comment' => '员工姓名'])]
    #[Assert\NotBlank(message: '员工姓名不能为空')]
    #[Assert\Length(max: 100, maxMessage: '员工姓名不能超过100个字符')]
    private string $name;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '所属部门'])]
    #[Assert\Length(max: 100, maxMessage: '部门名称不能超过100个字符')]
    private ?string $department = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '基本薪资'])]
    #[Assert\NotBlank(message: '基本薪资不能为空')]
    #[Assert\Length(max: 13, maxMessage: '基本薪资字符串长度不能超过13个字符')]
    #[Assert\GreaterThan(value: 0, message: '基本薪资必须大于0')]
    private string $baseSalary;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '专项附加扣除配置'])]
    #[Assert\Type(type: 'array', message: '专项附加扣除必须是数组类型')]
    private array $specialDeductions = [];

    #[ORM\Column(options: ['comment' => '入职日期'])]
    #[Assert\NotNull(message: '入职日期不能为空')]
    private \DateTimeImmutable $hireDate;

    #[ORM\Column(length: 18, nullable: true, options: ['comment' => '身份证号码'])]
    #[Assert\Length(max: 18, maxMessage: '身份证号码不能超过18位')]
    private ?string $idNumber = null;

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->name ?? '未知', $this->employeeNumber ?? '');
    }

    // Getter/Setter methods only - no business logic

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployeeNumber(): string
    {
        return $this->employeeNumber;
    }

    public function setEmployeeNumber(string $employeeNumber): void
    {
        $this->employeeNumber = $employeeNumber;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): void
    {
        $this->department = $department;
    }

    public function getBaseSalary(): string
    {
        return $this->baseSalary;
    }

    public function setBaseSalary(string $baseSalary): void
    {
        $this->baseSalary = $baseSalary;
    }

    /** @return array<string, mixed> */
    public function getSpecialDeductions(): array
    {
        return $this->specialDeductions;
    }

    /**
     * @param array<string, mixed> $specialDeductions
     */
    public function setSpecialDeductions(
        array $specialDeductions,
    ): void {
        $this->specialDeductions = $specialDeductions;
    }

    public function getHireDate(): \DateTimeImmutable
    {
        return $this->hireDate;
    }

    public function setHireDate(\DateTimeImmutable $hireDate): void
    {
        $this->hireDate = $hireDate;
    }

    public function getIdNumber(): ?string
    {
        return $this->idNumber;
    }

    public function setIdNumber(?string $idNumber): void
    {
        $this->idNumber = $idNumber;
    }
}
