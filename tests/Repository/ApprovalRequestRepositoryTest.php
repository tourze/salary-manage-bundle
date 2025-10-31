<?php

namespace Tourze\SalaryManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SalaryManageBundle\Entity\ApprovalRequest;
use Tourze\SalaryManageBundle\Entity\Employee;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Enum\ApprovalStatus;
use Tourze\SalaryManageBundle\Repository\ApprovalRequestRepository;

/**
 * ApprovalRequest Repository 测试
 * @internal
 */
#[CoversClass(ApprovalRequestRepository::class)]
#[RunTestsInSeparateProcesses]
class ApprovalRequestRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $em = self::getEntityManager();

        // 创建测试员工
        $employee = new Employee();
        $employee->setEmployeeNumber('TEST_AR_' . uniqid());
        $employee->setName('测试员工');
        $employee->setBaseSalary('10000.00');
        $employee->setHireDate(new \DateTimeImmutable());
        $em->persist($employee);

        // 创建测试薪资期间
        $period = new PayrollPeriod();
        $period->setYear((int) date('Y'));
        $period->setMonth((int) date('n'));
        $em->persist($period);

        $em->flush();

        // 创建审批请求
        $request = new ApprovalRequest();
        $request->setRequestId('REQ_' . uniqid());
        $request->setSalaryCalculations([]);
        $request->setPeriod($period);
        $request->setSubmitter($employee);
        $request->setStatus(ApprovalStatus::Pending);
        $request->setSubmittedAt(new \DateTimeImmutable());

        return $request;
    }

    protected function getRepository(): ApprovalRequestRepository
    {
        $repository = self::getEntityManager()->getRepository(ApprovalRequest::class);
        self::assertInstanceOf(ApprovalRequestRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // 子类可以实现额外的设置逻辑
    }

    public function testSaveAndRemoveMethods(): void
    {
        $repository = $this->getRepository();
        $request = $this->createNewEntity();
        $this->assertInstanceOf(ApprovalRequest::class, $request);

        $repository->save($request, true);

        $this->assertNotNull($request->getId());

        // 验证能够从数据库中找到保存的实体
        $foundRequest = $repository->find($request->getId());
        $this->assertNotNull($foundRequest);
        $this->assertEquals($request->getRequestId(), $foundRequest->getRequestId());

        // 测试删除
        $id = $request->getId();
        $repository->remove($request, true);

        // 验证已被删除
        $deletedRequest = $repository->find($id);
        $this->assertNull($deletedRequest);
    }

    public function testFindByStatus(): void
    {
        $repository = $this->getRepository();
        $request = $this->createNewEntity();
        $this->assertInstanceOf(ApprovalRequest::class, $request);
        $request->setStatus(ApprovalStatus::Pending);
        $repository->save($request, true);

        // 按状态查找
        $pendingRequests = $repository->findByStatus(ApprovalStatus::Pending);

        $this->assertIsArray($pendingRequests);
        $this->assertGreaterThanOrEqual(1, count($pendingRequests));

        // 验证返回的是正确类型
        foreach ($pendingRequests as $req) {
            $this->assertInstanceOf(ApprovalRequest::class, $req);
            $this->assertEquals(ApprovalStatus::Pending, $req->getStatus());
        }
    }

    public function testFindPendingRequests(): void
    {
        $repository = $this->getRepository();
        $request = $this->createNewEntity();
        $this->assertInstanceOf(ApprovalRequest::class, $request);
        $request->setStatus(ApprovalStatus::Pending);
        $repository->save($request, true);

        // 查找待审批的请求
        $pendingRequests = $repository->findPendingRequests();

        $this->assertIsArray($pendingRequests);
        $this->assertGreaterThanOrEqual(1, count($pendingRequests));

        // 验证都是待审批状态
        foreach ($pendingRequests as $req) {
            $this->assertEquals(ApprovalStatus::Pending, $req->getStatus());
        }
    }

    public function testFindByRequestId(): void
    {
        $repository = $this->getRepository();
        $request = $this->createNewEntity();
        $this->assertInstanceOf(ApprovalRequest::class, $request);
        $requestId = 'UNIQUE_REQ_' . uniqid();
        $request->setRequestId($requestId);
        $repository->save($request, true);

        // 按 requestId 查找
        $foundRequest = $repository->findByRequestId($requestId);

        $this->assertNotNull($foundRequest);
        $this->assertEquals($requestId, $foundRequest->getRequestId());
        $this->assertEquals($request->getId(), $foundRequest->getId());
    }

    public function testDifferentStatuses(): void
    {
        $repository = $this->getRepository();

        // 创建不同状态的请求
        $statuses = [
            ApprovalStatus::Pending,
            ApprovalStatus::Approved,
            ApprovalStatus::Rejected,
        ];

        foreach ($statuses as $status) {
            $request = $this->createNewEntity();
            $this->assertInstanceOf(ApprovalRequest::class, $request);
            $request->setStatus($status);
            $request->setRequestId('REQ_' . $status->value . '_' . uniqid());
            $repository->save($request, true);

            $this->assertNotNull($request->getId());
            $this->assertEquals($status, $request->getStatus());
        }

        // 验证可以按不同状态查找
        foreach ($statuses as $status) {
            $requests = $repository->findByStatus($status);
            $this->assertIsArray($requests);
            $this->assertGreaterThanOrEqual(1, count($requests));
        }
    }
}