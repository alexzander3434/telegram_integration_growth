<?php

namespace App\Service;

use App\Repository\TelegramIntegrationRepository;
use Psr\Cache\CacheItemPoolInterface;

final class TelegramIntegrationCache
{
    private const CACHE_KEY = 'telegram_integrations.active_by_shop';

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly TelegramIntegrationRepository $integrationRepository,
    ) {
    }

    /**
     * @return array<string, array{botToken: string, chatId: string}>
     */
    public function rebuildActiveByShop(): array
    {
        $integrations = $this->integrationRepository->findBy(['enabled' => true]);

        $map = [];
        foreach ($integrations as $integration) {
            $shopId = (string) $integration->getShopId();
            $map[$shopId] = [
                'botToken' => $integration->getBotToken(),
                'chatId' => $integration->getChatId(),
            ];
        }

        $item = $this->cache->getItem(self::CACHE_KEY);
        $item->set($map);
        $this->cache->save($item);

        return $map;
    }

    /**
     * @return array<string, array{botToken: string, chatId: string}>
     */
    public function getActiveByShop(): array
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        if (!$item->isHit()) {
            return $this->rebuildActiveByShop();
        }

        /** @var array<string, array{botToken: string, chatId: string}> $value */
        $value = (array) $item->get();
        return $value;
    }

    public function upsertShopIntegration(string $shopId, string $botToken, string $chatId, bool $enabled): void
    {
        $map = $this->getActiveByShop();

        if ($enabled) {
            $map[$shopId] = ['botToken' => $botToken, 'chatId' => $chatId];
        } else {
            unset($map[$shopId]);
        }

        $item = $this->cache->getItem(self::CACHE_KEY);
        $item->set($map);
        $this->cache->save($item);
    }
}

