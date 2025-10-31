<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\SalaryCalculation;
use Tourze\SalaryManageBundle\Exception\SalaryCalculationException;

/**
 * 薪资计算器服务 - 业务逻辑在Service中
 * 负责协调各种计算规则执行薪资计算
 */
class SalaryCalculatorService implements SalaryCalculatorInterface
{
    /** @var CalculationRuleInterface[] */
    private array $rules = [];

    public function calculate(Employee $employee, PayrollPeriod $period): SalaryCalculation
    {
        $calculation = new SalaryCalculation();
        $calculation->setEmployee($employee);
        $calculation->setPeriod($period);

        // 按优先级排序规则
        $sortedRules = $this->getSortedRules();

        foreach ($sortedRules as $rule) {
            if ($rule->isApplicable($employee)) {
                try {
                    $salaryItem = $rule->calculate($employee, $period, $calculation->getContext());
                    $calculation->addItem($salaryItem);
                } catch (\Exception $e) {
                    throw new SalaryCalculationException(sprintf('计算规则 %s 执行失败: %s', $rule->getType(), $e->getMessage()), 0, $e);
                }
            }
        }

        // 数据验证 (映射需求R1.4)
        $this->validateCalculation($calculation);

        return $calculation;
    }

    public function addRule(CalculationRuleInterface $rule): void
    {
        $this->rules[$rule->getType()] = $rule;
    }

    public function removeRule(string $ruleType): void
    {
        unset($this->rules[$ruleType]);
    }

    public function getRules(): array
    {
        return array_values($this->rules);
    }

    /**
     * @return CalculationRuleInterface[]
     */
    private function getSortedRules(): array
    {
        $rules = array_values($this->rules);
        usort($rules, fn (CalculationRuleInterface $a, CalculationRuleInterface $b) => $a->getOrder() <=> $b->getOrder());

        return $rules;
    }

    private function validateCalculation(SalaryCalculation $calculation): void
    {
        if ($calculation->getGrossAmount() < 0) {
            throw new SalaryCalculationException('工资总额不能为负数');
        }

        if ($calculation->getItems()->isEmpty()) {
            throw new SalaryCalculationException('薪资计算结果不能为空');
        }
    }
}
