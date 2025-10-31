<?php

namespace Tourze\SalaryManageBundle\Interface;

interface ExternalSystemInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function connect(
        array $config,
    ): bool;

    public function authenticate(): bool;

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchData(
        string $endpoint,
        array $params = [],
    ): array;

    /**
     * @param array<string, mixed> $data
     */
    public function pushData(
        string $endpoint,
        array $data,
    ): bool;

    public function getLastSyncTime(): ?\DateTimeImmutable;

    public function updateSyncTime(\DateTimeImmutable $time): bool;

    /**
     * @return array<string, mixed>
     */
    public function getConnectionStatus(): array;

    public function disconnect(): bool;
}
