<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SalaryManageBundle\Entity\ApprovalRequest;
use Tourze\SalaryManageBundle\Enum\ApprovalStatus;

/**
 * ApprovalRequest Repository
 *
 * @extends ServiceEntityRepository<ApprovalRequest>
 *
 * @method ApprovalRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApprovalRequest|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method ApprovalRequest[]    findAll()
 * @method ApprovalRequest[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
#[AsRepository(entityClass: ApprovalRequest::class)]
class ApprovalRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApprovalRequest::class);
    }

    public function save(ApprovalRequest $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ApprovalRequest $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<ApprovalRequest>
     */
    public function findByStatus(ApprovalStatus $status): array
    {
        /** @var array<ApprovalRequest> */
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', $status)
            ->orderBy('a.submittedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return ApprovalRequest[]
     */
    public function findPendingRequests(): array
    {
        return $this->findByStatus(ApprovalStatus::Pending);
    }

    public function findByRequestId(string $requestId): ?ApprovalRequest
    {
        return $this->findOneBy(['requestId' => $requestId]);
    }
}
