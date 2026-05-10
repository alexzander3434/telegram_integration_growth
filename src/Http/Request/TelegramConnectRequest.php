<?php

namespace App\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class TelegramConnectRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public readonly mixed $botToken,

        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public readonly mixed $chatId,

        #[Assert\NotNull]
        #[Assert\Type('bool')]
        public readonly mixed $enabled,
    ) {
    }

    public function botTokenString(): string
    {
        return (string) $this->botToken;
    }

    public function chatIdString(): string
    {
        return (string) $this->chatId;
    }

    public function enabledBool(): bool
    {
        return (bool) $this->enabled;
    }
}

