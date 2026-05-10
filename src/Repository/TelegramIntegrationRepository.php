<?php

namespace App\Repository;

use App\Entity\TelegramIntegration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramIntegration>
 */
class TelegramIntegrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramIntegration::class);
    }

    public function findOneByShopId(int $shopId): ?TelegramIntegration
    {
        return $this->findOneBy(['shopId' => $shopId]);
    }
}

