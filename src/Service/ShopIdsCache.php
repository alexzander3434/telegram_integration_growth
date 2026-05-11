<?php

namespace App\Service;

use App\Repository\ShopRepository;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Кеширует набор id магазинов в pool `cache.app` (Redis в docker, array в test).
 * При промахе перечитывает все id из БД одним запросом.
 */
final class ShopIdsCache
{
    private const CACHE_KEY = 'shops.all_ids';

    private const TTL_SECONDS = 3600;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly ShopRepository $shopRepository,
    ) {
    }

    public function shopExists(int $shopId): bool
    {
        $map = $this->getIdMap();

        return isset($map[(string) $shopId]);
    }

    public function invalidate(): void
    {
        $this->cache->deleteItem(self::CACHE_KEY);
    }

    /**
     * @return array<string, true>
     */
    private function getIdMap(): array
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        if (!$item->isHit()) {
            return $this->rebuild();
        }

        /** @var array<string, true> $value */
        $value = (array) $item->get();

        return $value;
    }

    /**
     * @return array<string, true>
     */
    private function rebuild(): array
    {
        $map = [];
        foreach ($this->shopRepository->findAllIds() as $id) {
            $map[$id] = true;
        }

        $item = $this->cache->getItem(self::CACHE_KEY);
        $item->set($map);
        $item->expiresAfter(self::TTL_SECONDS);
        $this->cache->save($item);

        return $map;
    }
}
