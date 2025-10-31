<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;

/**
 * 薪资计算引擎接口 (映射需求R1.1)
 * 负责执行薪资计算操作
 */
interface SalaryCalculatorInterface
{
    /**
     * 计算员工薪资
     *
     * @param Employee $employee 员工信息
     * @param PayrollPeriod $period 薪资期间
     * @return SalaryCalculation 计算结果
     */
    public function calculate(Employee $employee, PayrollPeriod $period): SalaryCalculation;

    /**
     * 添加计算规则
     *
     * @param CalculationRuleInterface $rule 计算规则
     */
    public function addRule(CalculationRuleInterface $rule): void;

    /**
     * 移除计算规则
     *
     * @param string $ruleType 规则类型
     */
    public function removeRule(string $ruleType): void;

    /**
     * 获取所有计算规则
     *
     * @return array<int, CalculationRuleInterface>
     */
    public function getRules(): array;
}
