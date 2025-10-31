<?php

namespace Tourze\SalaryManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\SalaryManageBundle\Entity\Employee;

class EmployeeFixtures extends Fixture
{
    public const EMPLOYEE_1_REFERENCE = 'employee-1';
    public const EMPLOYEE_2_REFERENCE = 'employee-2';
    public const EMPLOYEE_3_REFERENCE = 'employee-3';
    public const EMPLOYEE_4_REFERENCE = 'employee-4';
    public const EMPLOYEE_5_REFERENCE = 'employee-5';

    public function load(ObjectManager $manager): void
    {
        // 创建示例员工数据
        $employees = [
            [
                'employeeNumber' => 'EMP001',
                'name' => '张三',
                'department' => '技术部',
                'baseSalary' => '15000.00',
                'specialDeductions' => [
                    'housing_loan' => 2000,
                    'children_education' => 1000,
                ],
                'idNumber' => '110101199001011234',
            ],
            [
                'employeeNumber' => 'EMP002',
                'name' => '李四',
                'department' => '人事部',
                'baseSalary' => '12000.00',
                'specialDeductions' => [
                    'rent' => 1500,
                ],
                'idNumber' => '110101199002021234',
            ],
            [
                'employeeNumber' => 'EMP003',
                'name' => '王五',
                'department' => '财务部',
                'baseSalary' => '18000.00',
                'specialDeductions' => [
                    'housing_loan' => 3000,
                    'elderly_care' => 2000,
                    'continuing_education' => 400,
                ],
                'idNumber' => '110101199003031234',
            ],
            [
                'employeeNumber' => 'EMP004',
                'name' => '赵六',
                'department' => '销售部',
                'baseSalary' => '10000.00',
                'specialDeductions' => [],
                'idNumber' => '110101199004041234',
            ],
            [
                'employeeNumber' => 'EMP005',
                'name' => '刘七',
                'department' => '技术部',
                'baseSalary' => '25000.00',
                'specialDeductions' => [
                    'housing_loan' => 4000,
                    'children_education' => 2000,
                    'elderly_care' => 2000,
                ],
                'idNumber' => '110101199005051234',
            ],
        ];

        foreach ($employees as $index => $employeeData) {
            $employee = new Employee();
            $employee->setEmployeeNumber($employeeData['employeeNumber']);
            $employee->setName($employeeData['name']);
            $employee->setDepartment($employeeData['department']);
            $employee->setBaseSalary($employeeData['baseSalary']);
            $employee->setSpecialDeductions($employeeData['specialDeductions']);
            $employee->setIdNumber($employeeData['idNumber']);

            // 设置不同的入职时间
            $hireDate = new \DateTimeImmutable(sprintf('-%d months', 6 + $index * 2));
            $employee->setHireDate($hireDate);

            $manager->persist($employee);

            // 设置引用，供其他 fixtures 使用
            if (0 === $index) {
                $this->addReference(self::EMPLOYEE_1_REFERENCE, $employee);
            } elseif (1 === $index) {
                $this->addReference(self::EMPLOYEE_2_REFERENCE, $employee);
            } elseif (2 === $index) {
                $this->addReference(self::EMPLOYEE_3_REFERENCE, $employee);
            } elseif (3 === $index) {
                $this->addReference(self::EMPLOYEE_4_REFERENCE, $employee);
            } elseif (4 === $index) {
                $this->addReference(self::EMPLOYEE_5_REFERENCE, $employee);
            }
        }

        $manager->flush();
    }
}
