<?php

namespace App\Entity;

use App\Repository\TelegramSendLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TelegramSendLogRepository::class)]
#[ORM\Table(name: 'telegram_send_log')]
#[ORM\UniqueConstraint(name: 'uniq_telegram_send_log_shop_order', columns: ['shop_id', 'order_id'])]
class TelegramSendLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'shop_id', type: Types::TEXT)]
    private string $shopId;

    #[ORM\Column(name: 'order_id', type: Types::BIGINT)]
    private int $orderId;

    #[ORM\Column(type: Types::TEXT)]
    private string $message;

    #[ORM\Column(length: 16, enumType: TelegramSendStatus::class)]
    private TelegramSendStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error = null;

    #[ORM\Column(name: 'sent_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $sentAt;

    public function __construct(string $shopId, int $orderId, string $message, TelegramSendStatus $status, ?string $error, \DateTimeImmutable $sentAt)
    {
        $this->shopId = $shopId;
        $this->orderId = $orderId;
        $this->message = $message;
        $this->status = $status;
        $this->error = $error;
        $this->sentAt = $sentAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShopId(): string
    {
        return $this->shopId;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getStatus(): TelegramSendStatus
    {
        return $this->status;
    }

    public function setStatus(TelegramSendStatus $status): void
    {
        $this->status = $status;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): void
    {
        $this->sentAt = $sentAt;
    }
}
