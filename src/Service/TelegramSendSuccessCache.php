<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Successful Telegram sends keyed by shop + order (cache.app → Redis in prod).
 * Used for idempotency without hitting the DB on every duplicate message.
 */
final class TelegramSendSuccessCache
{
    private const KEY_PREFIX = 'telegram_send_success.';

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function isSuccessfulSendRecorded(string $shopId, int $orderId): bool
    {
        return $this->cache->getItem($this->itemKey($shopId, $orderId))->isHit();
    }

    public function recordSuccessfulSend(string $shopId, int $orderId, \DateTimeImmutable $sentAt): void
    {
        $item = $this->cache->getItem($this->itemKey($shopId, $orderId));
        $item->set((string) json_encode(
            ['sentAt' => $sentAt->format(DATE_ATOM)],
            JSON_THROW_ON_ERROR
        ));
        $this->cache->save($item);
    }

    private function itemKey(string $shopId, int $orderId): string
    {
        return self::KEY_PREFIX.hash('sha256', $shopId."\0".(string) $orderId);
    }
}
