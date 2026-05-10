<?php

namespace App\MessageHandler;

use App\Entity\TelegramSendLog;
use App\Entity\TelegramSendStatus;
use App\Message\OrderCreatedMessage;
use App\Repository\TelegramSendLogRepository;
use App\Service\TelegramIntegrationCache;
use App\Service\TelegramSendApiService;
use App\Service\TelegramSendSuccessCache;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class OrderCreatedMessageHandler
{
    public function __construct(
        private readonly TelegramIntegrationCache $integrations,
        private readonly TelegramSendApiService $telegramSend,
        private readonly TelegramSendSuccessCache $sendSuccessCache,
        private readonly TelegramSendLogRepository $sendLogRepo,
        private readonly EntityManagerInterface $em,
        private readonly LockFactory $lockFactory,
    ) {
    }

    public function __invoke(OrderCreatedMessage $message): void
    {
        $shopId = $message->shopId;
        $active = $this->integrations->getActiveByShop()[$shopId] ?? null;

        if ($active === null) {
            return;
        }

        $text = sprintf(
            "Новый заказ %s на сумму %s ₽, клиент\n%s",
            $message->number,
            $message->total,
            $message->customerName
        );

        $lock = $this->lockFactory->createLock(
            sprintf('telegram_order_notify_%s_%d', $shopId, $message->orderId),
            60.0
        );

        if (!$lock->acquire(true)) {
            return;
        }

        try {
            if ($this->sendSuccessCache->isSuccessfulSendRecorded($shopId, $message->orderId)) {
                return;
            }

            $existing = $this->sendLogRepo->findOneByShopIdAndOrderId($shopId, $message->orderId);
            if ($existing !== null && $existing->getStatus() === TelegramSendStatus::SENT) {
                $this->sendSuccessCache->recordSuccessfulSend($shopId, $message->orderId, $existing->getSentAt());

                return;
            }

            $now = new \DateTimeImmutable('now');

            try {
                $this->telegramSend->sendMessage($active['botToken'], $active['chatId'], $text);

                if ($existing !== null) {
                    $existing->setMessage($text);
                    $existing->setStatus(TelegramSendStatus::SENT);
                    $existing->setError(null);
                    $existing->setSentAt($now);
                } else {
                    $this->em->persist(new TelegramSendLog(
                        shopId: $shopId,
                        orderId: $message->orderId,
                        message: $text,
                        status: TelegramSendStatus::SENT,
                        error: null,
                        sentAt: $now,
                    ));
                }

                $this->em->flush();
                $this->sendSuccessCache->recordSuccessfulSend($shopId, $message->orderId, $now);
            } catch (\Throwable $e) {
                $err = $e->getMessage();
                if ($existing !== null) {
                    $existing->setMessage($text);
                    $existing->setStatus(TelegramSendStatus::FAILED);
                    $existing->setError($err);
                    $existing->setSentAt($now);
                } else {
                    $this->em->persist(new TelegramSendLog(
                        shopId: $shopId,
                        orderId: $message->orderId,
                        message: $text,
                        status: TelegramSendStatus::FAILED,
                        error: $err,
                        sentAt: $now,
                    ));
                }

                $this->em->flush();
                // Do not rethrow: message is persisted as FAILED; avoid infinite Messenger retries.
            }
        } finally {
            $lock->release();
        }
    }
}
