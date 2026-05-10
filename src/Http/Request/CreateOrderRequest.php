<?php

namespace App\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateOrderRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public readonly mixed $number,

        #[Assert\NotNull]
        #[Assert\Type('numeric')]
        public readonly mixed $total,

        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public readonly mixed $customerName,
    ) {
    }

    public function numberString(): string
    {
        return (string) $this->number;
    }

    public function totalNumeric(): string
    {
        // Keep as string for NUMERIC to avoid float rounding
        return (string) $this->total;
    }

    public function customerNameString(): string
    {
        return (string) $this->customerName;
    }
}

