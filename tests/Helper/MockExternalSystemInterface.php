<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Tests\Helper;

use Tourze\SalaryManageBundle\Interface\ExternalSystemInterface;

/**
 * Mock实现用于测试ExternalSystemInterface
 */
class MockExternalSystemInterface implements ExternalSystemInterface
{
    private bool $authResult = true;

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $fetchDataResults = [];

    /** @var callable|null */
    private $fetchDataCallback;

    /** @var array<string, int> */
    private array $callCounts = [];

    /** @var array<string, int> */
    private array $expectedCalls = [];

    public function setAuthResult(bool $result): void
    {
        $this->authResult = $result;
    }

    /** @param array<int, array<string, mixed>> $data */
    public function setFetchDataResult(string $endpoint, array $data): void
    {
        $this->fetchDataResults[$endpoint] = $data;
    }

    public function setFetchDataCallback(callable $callback): void
    {
        $this->fetchDataCallback = $callback;
    }

    public function expectCall(string $method, int $times = 1): void
    {
        $this->expectedCalls[$method] = $times;
        $this->callCounts[$method] = 0;
    }

    /** @param array<string, mixed> $config */
    public function connect(array $config): bool
    {
        return true;
    }

    public function authenticate(): bool
    {
        $this->callCounts['authenticate'] = ($this->callCounts['authenticate'] ?? 0) + 1;

        return $this->authResult;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchData(string $endpoint, array $params = []): array
    {
        $this->callCounts['fetchData'] = ($this->callCounts['fetchData'] ?? 0) + 1;

        if (null !== $this->fetchDataCallback) {
            $result = ($this->fetchDataCallback)($endpoint, $params);
            if (!is_array($result)) {
                return [];
            }
            /** @var array<int, array<string, mixed>> */
            return array_values(array_filter($result, fn($item): bool => is_array($item)));
        }

        /** @var array<int, array<string, mixed>> */
        return array_values($this->fetchDataResults[$endpoint] ?? []);
    }

    /** @param array<mixed> $data */
    public function pushData(string $endpoint, array $data): bool
    {
        return true;
    }

    public function getLastSyncTime(): ?\DateTimeImmutable
    {
        return null;
    }

    public function updateSyncTime(\DateTimeImmutable $time): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function getConnectionStatus(): array
    {
        return ['status' => 'connected'];
    }

    public function disconnect(): bool
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
