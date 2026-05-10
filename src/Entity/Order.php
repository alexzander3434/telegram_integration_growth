<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[ORM\UniqueConstraint(name: 'uniq_orders_number', columns: ['number'])]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(name: 'shop_id', type: 'text')]
    private string $shopId;

    #[ORM\Column(type: 'text')]
    private string $number;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $total;

    #[ORM\Column(name: 'customer_name', type: 'text')]
    private string $customerName;

    #[ORM\Column(name: 'created_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $shopId, string $number, string $total, string $customerName)
    {
        $this->shopId = $shopId;
        $this->number = $number;
        $this->total = $total;
        $this->customerName = $customerName;
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShopId(): string
    {
        return $this->shopId;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

