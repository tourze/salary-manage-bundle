<?php

namespace Tourze\SalaryManageBundle\Service;

use Tourze\SalaryManageBundle\Enum\InsuranceType;
use Tourze\SalaryManageBundle\Interface\RegionalConfigProviderInterface;

class DefaultRegionalConfigProvider implements RegionalConfigProviderInterface
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct()
    {
        $this->config = $this->loadDefaultConfig();
    }

    /** @return array<string, float> */
    public function getInsuranceRates(string $region, InsuranceType $insuranceType): array
    {
        $regionConfig = $this->config[$region] ?? $this->config['default'];

        if (!is_array($regionConfig) || !isset($regionConfig['rates']) || !is_array($regionConfig['rates'])) {
            return [
                'employer_rate' => $insuranceType->getStandardEmployerRate(),
                'employee_rate' => $insuranceType->getStandardEmployeeRate(),
            ];
        }

        $rates = $regionConfig['rates'][$insuranceType->value] ?? null;
        if (!is_array($rates)) {
            return [
                'employer_rate' => $insuranceType->getStandardEmployerRate(),
                'employee_rate' => $insuranceType->getStandardEmployeeRate(),
            ];
        }

        return [
            'employer_rate' => is_float($rates['employer_rate'] ?? null) ? $rates['employer_rate'] : $insuranceType->getStandardEmployerRate(),
            'employee_rate' => is_float($rates['employee_rate'] ?? null) ? $rates['employee_rate'] : $insuranceType->getStandardEmployeeRate(),
        ];
    }

    /** @return array<string, float> */
    public function getContributionLimits(string $region, InsuranceType $insuranceType, int $year): array
    {
        $regionConfig = $this->config[$region] ?? $this->config['default'];

        if (!is_array($regionConfig) || !isset($regionConfig['limits']) || !is_array($regionConfig['limits'])) {
            return [
                'min_base' => 3000.0,
                'max_base' => 30000.0,
            ];
        }

        $yearConfig = $regionConfig['limits'][$year] ?? $regionConfig['limits'][2025] ?? null;
        if (!is_array($yearConfig)) {
            return [
                'min_base' => 3000.0,
                'max_base' => 30000.0,
            ];
        }

        $limits = $yearConfig[$insuranceType->value] ?? null;
        if (!is_array($limits)) {
            return [
                'min_base' => 3000.0,
                'max_base' => 30000.0,
            ];
        }

        return [
            'min_base' => is_float($limits['min_base'] ?? null) ? $limits['min_base'] : 3000.0,
            'max_base' => is_float($limits['max_base'] ?? null) ? $limits['max_base'] : 30000.0,
        ];
    }

    /** @return array<int, string> */
    public function getSupportedRegions(): array
    {
        return array_keys($this->config);
    }

    public function isRegionSupported(string $region): bool
    {
        return isset($this->config[$region]);
    }

    /** @return array<string, mixed> */
    public function getDefaultConfig(string $region): array
    {
        /** @var array<string, mixed> */
        $defaultConfig = $this->config['default'];
        $config = $this->config[$region] ?? $defaultConfig;

        if (!is_array($config)) {
            return [];
        }

        /** @var array<string, mixed> */
        return $config;
    }

    /** @return array<string, mixed> */
    private function loadDefaultConfig(): array
    {
        return [
            'default' => [
                'name' => '全国通用',
                'rates' => [
                    'pension' => ['employer_rate' => 0.20, 'employee_rate' => 0.08],
                    'medical' => ['employer_rate' => 0.08, 'employee_rate' => 0.02],
                    'unemployment' => ['employer_rate' => 0.007, 'employee_rate' => 0.003],
                    'work_injury' => ['employer_rate' => 0.005, 'employee_rate' => 0.0],
                    'maternity' => ['employer_rate' => 0.008, 'employee_rate' => 0.0],
                    'housing_fund' => ['employer_rate' => 0.12, 'employee_rate' => 0.12],
                ],
                'limits' => [
                    2025 => [
                        'pension' => ['min_base' => 3000.0, 'max_base' => 30000.0],
                        'medical' => ['min_base' => 3000.0, 'max_base' => 30000.0],
                        'unemployment' => ['min_base' => 3000.0, 'max_base' => 30000.0],
                        'work_injury' => ['min_base' => 3000.0, 'max_base' => 30000.0],
                        'maternity' => ['min_base' => 3000.0, 'max_base' => 30000.0],
                        'housing_fund' => ['min_base' => 1500.0, 'max_base' => 25000.0],
                    ],
                ],
            ],
            'beijing' => [
                'name' => '北京市',
                'rates' => [
                    'pension' => ['employer_rate' => 0.19, 'employee_rate' => 0.08],
                    'medical' => ['employer_rate' => 0.095, 'employee_rate' => 0.02],
                    'unemployment' => ['employer_rate' => 0.008, 'employee_rate' => 0.002],
                    'work_injury' => ['employer_rate' => 0.002, 'employee_rate' => 0.0],
                    'maternity' => ['employer_rate' => 0.008, 'employee_rate' => 0.0],
                    'housing_fund' => ['employer_rate' => 0.12, 'employee_rate' => 0.12],
                ],
                'limits' => [
                    2025 => [
                        'pension' => ['min_base' => 4800.0, 'max_base' => 35000.0],
                        'medical' => ['min_base' => 4800.0, 'max_base' => 35000.0],
                        'unemployment' => ['min_base' => 4800.0, 'max_base' => 35000.0],
                        'work_injury' => ['min_base' => 4800.0, 'max_base' => 35000.0],
                        'maternity' => ['min_base' => 4800.0, 'max_base' => 35000.0],
                        'housing_fund' => ['min_base' => 2000.0, 'max_base' => 28000.0],
                    ],
                ],
            ],
            'shanghai' => [
                'name' => '上海市',
                'rates' => [
                    'pension' => ['employer_rate' => 0.20, 'employee_rate' => 0.08],
                    'medical' => ['employer_rate' => 0.095, 'employee_rate' => 0.02],
                    'unemployment' => ['employer_rate' => 0.005, 'employee_rate' => 0.005],
                    'work_injury' => ['employer_rate' => 0.0016, 'employee_rate' => 0.0],
                    'maternity' => ['employer_rate' => 0.01, 'employee_rate' => 0.0],
                    'housing_fund' => ['employer_rate' => 0.07, 'employee_rate' => 0.07],
                ],
                'limits' => [
                    2025 => [
                        'pension' => ['min_base' => 5500.0, 'max_base' => 36000.0],
                        'medical' => ['min_base' => 5500.0, 'max_base' => 36000.0],
                        'unemployment' => ['min_base' => 5500.0, 'max_base' => 36000.0],
                        'work_injury' => ['min_base' => 5500.0, 'max_base' => 36000.0],
                        'maternity' => ['min_base' => 5500.0, 'max_base' => 36000.0],
                        'housing_fund' => ['min_base' => 2500.0, 'max_base' => 30000.0],
                    ],
                ],
            ],
            'guangzhou' => [
                'name' => '广州市',
                'rates' => [
                    'pension' => ['employer_rate' => 0.14, 'employee_rate' => 0.08],
                    'medical' => ['employer_rate' => 0.065, 'employee_rate' => 0.02],
                    'unemployment' => ['employer_rate' => 0.0048, 'employee_rate' => 0.002],
                    'work_injury' => ['employer_rate' => 0.002, 'employee_rate' => 0.0],
                    'maternity' => ['employer_rate' => 0.0085, 'employee_rate' => 0.0],
                    'housing_fund' => ['employer_rate' => 0.12, 'employee_rate' => 0.12],
                ],
                'limits' => [
                    2025 => [
                        'pension' => ['min_base' => 4200.0, 'max_base' => 32000.0],
                        'medical' => ['min_base' => 4200.0, 'max_base' => 32000.0],
                        'unemployment' => ['min_base' => 4200.0, 'max_base' => 32000.0],
                        'work_injury' => ['min_base' => 4200.0, 'max_base' => 32000.0],
                        'maternity' => ['min_base' => 4200.0, 'max_base' => 32000.0],
                        'housing_fund' => ['min_base' => 1800.0, 'max_base' => 25000.0],
                    ],
                ],
            ],
            'shenzhen' => [
                'name' => '深圳市',
                'rates' => [
                    'pension' => ['employer_rate' => 0.13, 'employee_rate' => 0.08],
                    'medical' => ['employer_rate' => 0.045, 'employee_rate' => 0.02],
                    'unemployment' => ['employer_rate' => 0.007, 'employee_rate' => 0.003],
                    'work_injury' => ['employer_rate' => 0.0014, 'employee_rate' => 0.0],
                    'maternity' => ['employer_rate' => 0.0045, 'employee_rate' => 0.0],
                    'housing_fund' => ['employer_rate' => 0.13, 'employee_rate' => 0.13],
                ],
                'limits' => [
                    2025 => [
                        'pension' => ['min_base' => 4500.0, 'max_base' => 34000.0],
                        'medical' => ['min_base' => 4500.0, 'max_base' => 34000.0],
                        'unemployment' => ['min_base' => 4500.0, 'max_base' => 34000.0],
                        'work_injury' => ['min_base' => 4500.0, 'max_base' => 34000.0],
                        'maternity' => ['min_base' => 4500.0, 'max_base' => 34000.0],
                        'housing_fund' => ['min_base' => 2000.0, 'max_base' => 27000.0],
                    ],
                ],
            ],
        ];
    }
}
