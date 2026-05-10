<?php

namespace App\Message;

final class OrderCreatedMessage
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $shopId,
        public readonly string $number,
        public readonly string $total,
        public readonly string $customerName,
        public readonly string $createdAt,
    ) {
    }
}

