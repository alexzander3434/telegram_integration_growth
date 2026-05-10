<?php

namespace App\Entity;

use App\Repository\TelegramIntegrationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TelegramIntegrationRepository::class)]
#[ORM\Table(
    name: 'telegram_integrations',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_telegram_integrations_shop_id', columns: ['shop_id'])]
)]
#[ORM\HasLifecycleCallbacks]
class TelegramIntegration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(name: 'shop_id', type: 'bigint', unique: true)]
    private int $shopId;

    #[ORM\Column(name: 'bot_token', type: 'text')]
    private string $botToken;

    #[ORM\Column(name: 'chat_id', type: 'text')]
    private string $chatId;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled;

    #[ORM\Column(name: 'created_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(int $shopId, string $botToken, string $chatId, bool $enabled)
    {
        $this->shopId = $shopId;
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->enabled = $enabled;
        $now = new \DateTimeImmutable('now');
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function getBotToken(): string
    {
        return $this->botToken;
    }

    public function setBotToken(string $botToken): void
    {
        $this->botToken = $botToken;
    }

    public function getChatId(): string
    {
        return $this->chatId;
    }

    public function setChatId(string $chatId): void
    {
        $this->chatId = $chatId;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now');
    }
}

