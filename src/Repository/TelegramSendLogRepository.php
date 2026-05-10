<?php

namespace App\Repository;

use App\Entity\TelegramSendLog;
use App\Entity\TelegramSendStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramSendLog>
 */
final class TelegramSendLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramSendLog::class);
    }

    public function findOneByShopIdAndOrderId(string $shopId, int $orderId): ?TelegramSendLog
    {
        return $this->findOneBy(['shopId' => $shopId, 'orderId' => $orderId]);
    }

    public function findLastSuccessfulSentAt(string $shopId): ?\DateTimeImmutable
    {
        $qb = $this->createQueryBuilder('l')
            ->select('MAX(l.sentAt)')
            ->where('l.shopId = :shopId')
            ->andWhere('l.status = :status')
            ->setParameter('shopId', $shopId)
            ->setParameter('status', TelegramSendStatus::SENT);

        $value = $qb->getQuery()->getSingleScalarResult();
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        return new \DateTimeImmutable((string) $value);
    }

    public function countByShopAndStatusSince(string $shopId, TelegramSendStatus $status, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.shopId = :shopId')
            ->andWhere('l.status = :status')
            ->andWhere('l.sentAt >= :since')
            ->setParameter('shopId', $shopId)
            ->setParameter('status', $status)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
