<?php

namespace App\EventSubscriber;

use App\Service\TelegramIntegrationCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

final class TelegramIntegrationCacheWarmupSubscriber implements EventSubscriberInterface
{
    private bool $done = false;
    private LockFactory $lockFactory;

    public function __construct(private readonly TelegramIntegrationCache $cache)
    {
        // Works without extra infra; only ensures single warmup per container at a time.
        $this->lockFactory = new LockFactory(new FlockStore(sys_get_temp_dir()));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1000],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $this->done) {
            return;
        }
        $this->done = true;

        $lock = $this->lockFactory->createLock('telegram_integrations_cache_warmup', 30.0);
        if (!$lock->acquire()) {
            return;
        }

        try {
            // Always rebuild once per container start to match DB state.
            $this->cache->rebuildActiveByShop();
        } finally {
            $lock->release();
        }
    }
}

