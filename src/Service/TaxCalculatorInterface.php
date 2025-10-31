<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\TaxBracket;
use Tourze\SalaryManageBundle\Entity\TaxResult;

/**
 * 税务计算器接口 (映射需求R2.1)
 * 负责个人所得税的计算
 */
interface TaxCalculatorInterface
{
    /**
     * 计算个人所得税
     *
     * @param Employee $employee 员工信息
     * @param float $taxableIncome 应税收入
     * @param array<string, mixed> $context 计算上下文（累计收入、已扣税额等）
     * @return TaxResult 税务计算结果
     */
    public function calculate(Employee $employee, float $taxableIncome, array $context = []): TaxResult;

    /**
     * 获取税率表
     *
     * @return array<int, TaxBracket> 当前适用的税率表
     */
    public function getTaxBrackets(): array;

    /**
     * 验证税务计算是否符合法规
     *
     * @param TaxResult $result 计算结果
     * @return bool 是否合规
     */
    public function validateComplianceRules(TaxResult $result): bool;
}
