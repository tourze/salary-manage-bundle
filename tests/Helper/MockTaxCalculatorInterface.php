<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Tests\Helper;

use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\TaxBracket;
use Tourze\SalaryManageBundle\Entity\TaxResult;
use Tourze\SalaryManageBundle\Service\TaxCalculatorInterface;

/**
 * Mock实现用于测试TaxCalculatorInterface
 */
class MockTaxCalculatorInterface implements TaxCalculatorInterface
{
    private ?TaxResult $result = null;

    /** @var array<string, int> */
    private array $callCounts = [];

    /** @var array<string, int> */
    private array $expectedCalls = [];

    public function setCalculateResult(TaxResult $result): void
    {
        $this->result = $result;
    }

    public function expectCall(string $method, int $times = 1): void
    {
        $this->expectedCalls[$method] = $times;
        $this->callCounts[$method] = 0;
    }

    /** @param array<string, mixed> $context */
    public function calculate(Employee $employee, float $taxableIncome, array $context = []): TaxResult
    {
        $this->callCounts['calculate'] = ($this->callCounts['calculate'] ?? 0) + 1;
        if (null === $this->result) {
            throw new \RuntimeException('TaxResult not set');
        }

        return $this->result;
    }

    /** @return array<int, TaxBracket> */
    public function getTaxBrackets(): array
    {
        return [];
    }

    public function validateComplianceRules(TaxResult $result): bool
    {
        return true;
    }

    public function verifyExpectedCalls(): void
    {
        foreach ($this->expectedCalls as $method => $expectedCount) {
            $actualCount = $this->callCounts[$method] ?? 0;
            if ($actualCount !== $expectedCount) {
                throw new \RuntimeException("Expected {$method} to be called {$expectedCount} times, but was called {$actualCount} times");
            }
        }
    }
}
