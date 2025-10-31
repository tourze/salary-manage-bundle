<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Entity\TaxBracket;

/**
 * 税率表提供者 - 提供当前适用的个人所得税税率表
 * 根据2025年个人所得税法规定
 */
class TaxBracketProvider
{
    /**
     * 获取工资薪金个人所得税税率表（2025年）
     *
     * @return TaxBracket[]
     */
    public function getSalaryTaxBrackets(): array
    {
        return [
            new TaxBracket(1, 0, 36000, 0.03, 0),           // 3万元以下：3%
            new TaxBracket(2, 36000, 144000, 0.10, 2520),   // 3万-14.4万元：10%
            new TaxBracket(3, 144000, 300000, 0.20, 16920), // 14.4万-30万元：20%
            new TaxBracket(4, 300000, 420000, 0.25, 31920), // 30万-42万元：25%
            new TaxBracket(5, 420000, 660000, 0.30, 52920), // 42万-66万元：30%
            new TaxBracket(6, 660000, 960000, 0.35, 85920), // 66万-96万元：35%
            new TaxBracket(7, 960000, INF, 0.45, 181920),   // 96万元以上：45%
        ];
    }

    /**
     * 根据年收入查找适用的税率档次
     */
    public function findApplicableBracket(float $annualIncome): ?TaxBracket
    {
        foreach ($this->getSalaryTaxBrackets() as $bracket) {
            if ($bracket->isApplicable($annualIncome)) {
                return $bracket;
            }
        }

        return null;
    }

    /**
     * 获取基本减除费用（免征额）
     * 2025年标准：每月5000元，全年60000元
     *
     * 不考虑并发 - 只返回常量值，不涉及共享状态
     */
    public function getBasicDeduction(): float
    {
        return 60000; // 年度基本减除费用
    }

    /**
     * 获取基本减除费用（月度）
     *
     * 不考虑并发 - 只返回常量值，不涉及共享状态
     */
    public function getMonthlyBasicDeduction(): float
    {
        return 5000; // 月度基本减除费用
    }

    /**
     * 验证税率表的完整性和正确性
     */
    public function validateTaxBrackets(): bool
    {
        $brackets = $this->getSalaryTaxBrackets();

        // 检查是否有7个档次
        if (7 !== count($brackets)) {
            return false;
        }

        // 检查档次连续性
        for ($i = 0; $i < count($brackets) - 1; ++$i) {
            if ($brackets[$i]->getMaxIncome() !== $brackets[$i + 1]->getMinIncome()) {
                return false;
            }
        }

        // 检查税率递增
        for ($i = 0; $i < count($brackets) - 1; ++$i) {
            if ($brackets[$i]->getRate() >= $brackets[$i + 1]->getRate()) {
                return false;
            }
        }

        return true;
    }
}
