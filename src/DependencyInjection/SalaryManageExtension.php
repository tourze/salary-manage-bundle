<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class SalaryManageExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }

    public function getAlias(): string
    {
        return 'salary_manage';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        parent::load($configs, $container);

        // 加载 Doctrine 配置
        $loader = new YamlFileLoader($container, new FileLocator($this->getConfigDir()));
        if (file_exists($this->getConfigDir() . '/doctrine.yaml')) {
            $loader->load('doctrine.yaml');
        }
    }
}
