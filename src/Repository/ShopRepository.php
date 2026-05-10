<?php

namespace App\Repository;

use App\Entity\Shop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Shop>
 */
final class ShopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shop::class);
    }

    /**
     * @return Shop[]
     */
    public function findAllOrderedById(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

