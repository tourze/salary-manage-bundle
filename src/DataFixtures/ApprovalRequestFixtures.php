<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\SalaryManageBundle\Entity\ApprovalRequest;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Enum\ApprovalStatus;

class ApprovalRequestFixtures extends Fixture implements DependentFixtureInterface
{
    public const APPROVAL_REQUEST_1_REFERENCE = 'approval-request-1';
    public const APPROVAL_REQUEST_2_REFERENCE = 'approval-request-2';
    public const APPROVAL_REQUEST_3_REFERENCE = 'approval-request-3';

    public function load(ObjectManager $manager): void
    {
        // 创建审批请求数据
        $requests = [
            [
                'requestId' => 'AR-' . date('Ym') . '-001',
                'period' => PayrollPeriodFixtures::PERIOD_1_REFERENCE,
                'submitter' => EmployeeFixtures::EMPLOYEE_1_REFERENCE,
                'status' => ApprovalStatus::Pending,
                'salaryCalculations' => [],
                'comments' => '请审批当月薪资计算结果',
                'rejectionReason' => '',
                'approvalHistory' => [],
                'metadata' => [
                    'created_by' => 'system',
                    'priority' => 'normal',
                ],
            ],
            [
                'requestId' => 'AR-' . date('Ym') . '-002',
                'period' => PayrollPeriodFixtures::PERIOD_2_REFERENCE,
                'submitter' => EmployeeFixtures::EMPLOYEE_2_REFERENCE,
                'status' => ApprovalStatus::Approved,
                'salaryCalculations' => [],
                'comments' => '薪资计算无误，已审批通过',
                'rejectionReason' => '',
                'approvalHistory' => [
                    'approval_' . time() => [
                        'action' => 'approved',
                        'approver_id' => 1,
                        'approver_name' => '审批员',
                        'comments' => '薪资计算无误，已审批通过',
                        'timestamp' => new \DateTimeImmutable('-1 day'),
                    ],
                ],
                'metadata' => [
                    'created_by' => 'system',
                    'priority' => 'high',
                ],
            ],
            [
                'requestId' => 'AR-' . date('Ym') . '-003',
                'period' => PayrollPeriodFixtures::PERIOD_3_REFERENCE,
                'submitter' => EmployeeFixtures::EMPLOYEE_3_REFERENCE,
                'status' => ApprovalStatus::Rejected,
                'salaryCalculations' => [],
                'comments' => '',
                'rejectionReason' => '薪资计算存在错误，请重新核对',
                'approvalHistory' => [
                    'rejection_' . time() => [
                        'action' => 'rejected',
                        'approver_id' => 1,
                        'approver_name' => '审批员',
                        'reason' => '薪资计算存在错误，请重新核对',
                        'timestamp' => new \DateTimeImmutable('-2 days'),
                    ],
                ],
                'metadata' => [
                    'created_by' => 'system',
                    'priority' => 'normal',
                ],
            ],
        ];

        foreach ($requests as $index => $requestData) {
            $request = new ApprovalRequest();
            $request->setRequestId($requestData['requestId']);
            $request->setPeriod($this->getReference($requestData['period'], PayrollPeriod::class));
            $request->setSubmitter($this->getReference($requestData['submitter'], Employee::class));
            $request->setStatus($requestData['status']);
            $request->setSalaryCalculations($requestData['salaryCalculations']);
            $request->setMetadata($requestData['metadata']);

            // 设置提交时间（不同时间）
            $submittedTime = new \DateTimeImmutable(sprintf('-%d days', $index + 1));
            $request->setSubmittedAt($submittedTime);

            $manager->persist($request);

            // 设置引用，供其他 fixtures 使用
            if (0 === $index) {
                $this->addReference(self::APPROVAL_REQUEST_1_REFERENCE, $request);
            } elseif (1 === $index) {
                $this->addReference(self::APPROVAL_REQUEST_2_REFERENCE, $request);
            } elseif (2 === $index) {
                $this->addReference(self::APPROVAL_REQUEST_3_REFERENCE, $request);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            EmployeeFixtures::class,
            PayrollPeriodFixtures::class,
        ];
    }
}
