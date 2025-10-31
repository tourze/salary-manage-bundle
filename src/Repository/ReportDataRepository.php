<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SalaryManageBundle\Entity\PayrollPeriod;
use Tourze\SalaryManageBundle\Entity\ReportData;

/**
 * @extends ServiceEntityRepository<ReportData>
 */
#[AsRepository(entityClass: ReportData::class)]
class ReportDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReportData::class);
    }

    /**
     * 根据报表类型查找报表
     *
     * @return array<int, ReportData>
     */
    public function findByReportType(string $reportType): array
    {
        return $this->findBy(['reportType' => $reportType], ['generatedAt' => 'DESC']);
    }

    /**
     * 根据期间查找报表
     *
     * @return array<int, ReportData>
     */
    public function findByPeriod(PayrollPeriod $period): array
    {
        return $this->findBy(['period' => $period], ['generatedAt' => 'DESC']);
    }

    /**
     * 根据报表类型和期间查找报表
     */
    public function findOneByReportTypeAndPeriod(string $reportType, PayrollPeriod $period): ?ReportData
    {
        return $this->findOneBy([
            'reportType' => $reportType,
            'period' => $period
        ]);
    }

    /**
     * 查找最新的报表
     */
    public function findLatest(): ?ReportData
    {
        return $this->findOneBy([], ['generatedAt' => 'DESC']);
    }

    /**
     * 根据报表类型查找最新的报表
     */
    public function findLatestByReportType(string $reportType): ?ReportData
    {
        return $this->findOneBy(['reportType' => $reportType], ['generatedAt' => 'DESC']);
    }

    /**
     * 删除指定期间的所有报表
     */
    public function deleteByPeriod(PayrollPeriod $period): int
    {
        $qb = $this->createQueryBuilder('rd');
        $qb->delete()
           ->where('rd.period = :period')
           ->setParameter('period', $period);

        $result = $qb->getQuery()->execute();
        return is_int($result) ? $result : 0;
    }

    /**
     * 统计指定期间的报表数量
     */
    public function countByPeriod(PayrollPeriod $period): int
    {
        $qb = $this->createQueryBuilder('rd');
        $qb->select('COUNT(rd.id)')
           ->where('rd.period = :period')
           ->setParameter('period', $period);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 查找指定日期之后生成的报表
     *
     * @return array<int, ReportData>
     */
    public function findGeneratedAfter(\DateTimeInterface $date): array
    {
        $qb = $this->createQueryBuilder('rd');
        $qb->where('rd.generatedAt > :date')
           ->setParameter('date', $date)
           ->orderBy('rd.generatedAt', 'DESC');

        /** @var array<int, ReportData> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 保存实体
     */
    public function save(ReportData $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(ReportData $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}