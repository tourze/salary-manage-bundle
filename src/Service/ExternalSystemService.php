<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Interface\ExternalSystemInterface;

class ExternalSystemService implements ExternalSystemInterface
{
    public function connect(
        /** @var array<string, mixed> */
        array $config,
    ): bool {
        return true;
    }

    public function authenticate(): bool
    {
        return true;
    }

    public function fetchData(
        string $endpoint,
        /** @var array<string, mixed> */
        array $params = [],
    ): array {
        return [];
    }

    public function pushData(
        string $endpoint,
        /** @var array<string, mixed> */
        array $data,
    ): bool {
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
        return [
            'connected' => true,
            'last_check' => new \DateTimeImmutable(),
        ];
    }

    public function disconnect(): bool
    {
        return true;
    }
}
