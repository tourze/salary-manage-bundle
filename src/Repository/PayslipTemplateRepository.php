<?php

declare(strict_types=1);

namespace Tourze\SalaryManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SalaryManageBundle\Entity\PayslipTemplate;

/**
 * @extends ServiceEntityRepository<PayslipTemplate>
 */
#[AsRepository(entityClass: PayslipTemplate::class)]
class PayslipTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PayslipTemplate::class);
    }

    /**
     * 查找默认模板
     */
    public function findDefaultTemplate(): ?PayslipTemplate
    {
        return $this->findOneBy(['isDefault' => true]);
    }

    /**
     * 根据格式查找模板
     *
     * @return PayslipTemplate[]
     */
    public function findByFormat(string $format): array
    {
        return $this->findBy(['format' => $format]);
    }

    /**
     * 查找所有可用模板
     *
     * @return array<PayslipTemplate>
     */
    public function findAllOrderedByName(): array
    {
        /** @var array<PayslipTemplate> */
        return $this->createQueryBuilder('p')
            ->orderBy('p.isDefault', 'DESC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(PayslipTemplate $entity, bool $flush = true): void
    {
        static::getEntityManager()->persist($entity);

        if ($flush) {
            static::getEntityManager()->flush();
        }
    }

    public function remove(PayslipTemplate $entity, bool $flush = true): void
    {
        static::getEntityManager()->remove($entity);

        if ($flush) {
            static::getEntityManager()->flush();
        }
    }
}
