<?php

namespace Tourze\SalaryManageBundle\Tests\Bundle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\SalaryManageBundle\SalaryManageBundle;

/**
 * 测试composer.json配置的正确性
 * 验收标准：确保包名、命名空间和描述符合规范要求
 * @internal
 */
#[CoversClass(SalaryManageBundle::class)]
class ComposerConfigurationTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $composerConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $composerPath = __DIR__ . '/../../composer.json';
        $content = file_get_contents($composerPath);
        if (false === $content) {
            throw new \RuntimeException('Could not read composer.json file');
        }
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Could not parse composer.json file');
        }
        // Ensure the keys are strings
        /** @var array<string, mixed> $stringKeyed */
        $stringKeyed = [];
        foreach ($decoded as $key => $value) {
            $stringKeyed[(string) $key] = $value;
        }
        $this->composerConfig = $stringKeyed;
    }

    public function testPackageNameIsCorrect(): void
    {
        $this->assertEquals(
            'tourze/salary-manage-bundle',
            $this->composerConfig['name'],
            'Package name should be tourze/salary-manage-bundle'
        );
    }

    public function testPackageDescriptionIsAboutSalaryManagement(): void
    {
        $description = $this->composerConfig['description'];
        $this->assertIsString($description, 'Description should be a string');

        $this->assertStringContainsString(
            '薪酬管理',
            $description,
            'Description should mention salary management (薪酬管理)'
        );

        $this->assertStringNotContainsString(
            '供应商',
            $description,
            'Description should NOT mention suppliers (供应商)'
        );
    }

    public function testPackageTypeIsSymfonyBundle(): void
    {
        $this->assertEquals(
            'symfony-bundle',
            $this->composerConfig['type'],
            'Package type should be symfony-bundle'
        );
    }

    public function testAutoloadNamespaceIsCorrect(): void
    {
        $autoload = $this->composerConfig['autoload'];
        $this->assertIsArray($autoload, 'Autoload should be an array');

        $psr4 = $autoload['psr-4'];
        $this->assertIsArray($psr4, 'PSR-4 should be an array');

        $this->assertArrayHasKey(
            'Tourze\SalaryManageBundle\\',
            $psr4,
            'Autoload namespace should be Tourze\SalaryManageBundle\\'
        );

        $this->assertEquals(
            'src',
            $psr4['Tourze\SalaryManageBundle\\'],
            'Autoload path should point to src directory'
        );
    }

    public function testAutoloadDevNamespaceIsCorrect(): void
    {
        $autoloadDev = $this->composerConfig['autoload-dev'];
        $this->assertIsArray($autoloadDev, 'Autoload-dev should be an array');

        $psr4 = $autoloadDev['psr-4'];
        $this->assertIsArray($psr4, 'PSR-4 should be an array');

        $this->assertArrayHasKey(
            'Tourze\SalaryManageBundle\Tests\\',
            $psr4,
            'Autoload-dev namespace should be Tourze\SalaryManageBundle\Tests\\'
        );

        $this->assertEquals(
            'tests',
            $psr4['Tourze\SalaryManageBundle\Tests\\'],
            'Autoload-dev path should point to tests directory'
        );
    }

    public function testKeywordsIncludeSalaryRelatedTerms(): void
    {
        $this->assertArrayHasKey('keywords', $this->composerConfig);
        $keywords = $this->composerConfig['keywords'];
        $this->assertIsArray($keywords, 'Keywords should be an array');

        $this->assertContains('salary', $keywords, 'Keywords should include salary');
        $this->assertContains('payroll', $keywords, 'Keywords should include payroll');
        $this->assertContains('symfony', $keywords, 'Keywords should include symfony');
        $this->assertContains('bundle', $keywords, 'Keywords should include bundle');
    }

    public function testRequiredDependenciesArePresent(): void
    {
        $this->assertArrayHasKey('require', $this->composerConfig);
        $require = $this->composerConfig['require'];
        $this->assertIsArray($require, 'Require should be an array');

        // 检查必需的PHP版本
        $this->assertArrayHasKey('php', $require);
        $phpVersion = $require['php'];
        $this->assertIsString($phpVersion, 'PHP version should be a string');
        $this->assertStringContainsString('8.2', $phpVersion);

        // 检查Symfony组件
        $this->assertArrayHasKey('symfony/framework-bundle', $require);
        $this->assertArrayHasKey('symfony/dependency-injection', $require);
    }
}
