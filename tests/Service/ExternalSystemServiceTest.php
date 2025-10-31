<?php

namespace Tourze\SalaryManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SalaryManageBundle\Service\ExternalSystemService;

/**
 * 外部系统服务测试
 * 验收标准：测试外部系统连接和数据同步功能
 * @internal
 */
#[CoversClass(ExternalSystemService::class)]
final class ExternalSystemServiceTest extends TestCase
{
    private ExternalSystemService $service;

    protected function setUp(): void
    {
        $this->service = new ExternalSystemService();
    }

    public function testConnectWithValidConfigShouldReturnTrue(): void
    {
        $config = [
            'host' => 'example.com',
            'port' => 8080,
            'timeout' => 30,
        ];

        $result = $this->service->connect($config);

        $this->assertTrue($result);
    }

    public function testConnectWithEmptyConfigShouldReturnTrue(): void
    {
        $result = $this->service->connect([]);

        $this->assertTrue($result);
    }

    public function testAuthenticateShouldReturnTrue(): void
    {
        $result = $this->service->authenticate();

        $this->assertTrue($result);
    }

    public function testFetchDataShouldReturnArray(): void
    {
        $endpoint = '/api/employees';
        $params = ['page' => 1, 'limit' => 10];

        $result = $this->service->fetchData($endpoint, $params);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFetchDataWithoutParamsShouldReturnArray(): void
    {
        $endpoint = '/api/employees';

        $result = $this->service->fetchData($endpoint);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testPushDataShouldReturnTrue(): void
    {
        $endpoint = '/api/salary-data';
        $data = [
            'employee_id' => 'EMP001',
            'amount' => 10000.00,
            'month' => '2024-01',
        ];

        $result = $this->service->pushData($endpoint, $data);

        $this->assertTrue($result);
    }

    public function testPushDataWithEmptyDataShouldReturnTrue(): void
    {
        $endpoint = '/api/salary-data';
        $data = [];

        $result = $this->service->pushData($endpoint, $data);

        $this->assertTrue($result);
    }

    public function testGetLastSyncTimeShouldReturnNull(): void
    {
        $result = $this->service->getLastSyncTime();

        $this->assertNull($result);
    }

    public function testUpdateSyncTimeShouldReturnTrue(): void
    {
        $time = new \DateTimeImmutable('2024-01-01 12:00:00');

        $result = $this->service->updateSyncTime($time);

        $this->assertTrue($result);
    }

    public function testGetConnectionStatusShouldReturnArray(): void
    {
        $result = $this->service->getConnectionStatus();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('connected', $result);
        $this->assertArrayHasKey('last_check', $result);
        $this->assertTrue($result['connected']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['last_check']);
    }

    public function testDisconnectShouldReturnTrue(): void
    {
        $result = $this->service->disconnect();

        $this->assertTrue($result);
    }

    public function testServiceCanBeInstantiatedMultipleTimes(): void
    {
        $service1 = new ExternalSystemService();
        $service2 = new ExternalSystemService();

        $this->assertInstanceOf(ExternalSystemService::class, $service1);
        $this->assertInstanceOf(ExternalSystemService::class, $service2);
        $this->assertNotSame($service1, $service2);
    }

    public function testServiceMethodsCanBeCalledMultipleTimes(): void
    {
        $config = ['host' => 'example.com'];
        $time = new \DateTimeImmutable();

        // 多次调用相同方法
        $this->assertTrue($this->service->connect($config));
        $this->assertTrue($this->service->connect($config));

        $this->assertTrue($this->service->authenticate());
        $this->assertTrue($this->service->authenticate());

        $this->assertIsArray($this->service->fetchData('/test'));
        $this->assertIsArray($this->service->fetchData('/test'));

        $this->assertTrue($this->service->updateSyncTime($time));
        $this->assertTrue($this->service->updateSyncTime($time));
    }
}
