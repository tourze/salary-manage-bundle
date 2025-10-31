<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryItem;

/**
 * 计算规则接口 (映射需求R1.3)
 * 定义薪资计算规则的标准契约
 */
interface CalculationRuleInterface
{
    /**
     * 获取规则类型
     *
     * @return string 规则类型标识
     */
    public function getType(): string;

    /**
     * 执行计算
     *
     * @param Employee $employee 员工信息
     * @param PayrollPeriod $period 薪资期间
     * @param array<string, mixed> $context 计算上下文
     * @return SalaryItem 薪资项目
     */
    public function calculate(Employee $employee, PayrollPeriod $period, array $context = []): SalaryItem;

    /**
     * 判断规则是否适用于该员工
     *
     * @param Employee $employee 员工信息
     * @return bool 是否适用
     */
    public function isApplicable(Employee $employee): bool;

    /**
     * 获取规则执行顺序
     *
     * @return int 执行顺序（数字越小越先执行）
     */
    public function getOrder(): int;
}
