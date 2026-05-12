<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * HTTP client for Telegram Bot API (sendMessage).
 *
 * Set env TELEGRAM_SIMULATE_SEND_FAILURE=1 to throw before outbound HTTP calls (dev/QA).
 */
final class TelegramSendApiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'bool:TELEGRAM_SIMULATE_SEND_FAILURE')]
        private readonly bool $simulateSendFailure,
    ) {
    }

    public function sendMessage(string $botToken, string $chatId, string $text): void
    {
        if ($this->simulateSendFailure) {
            throw new \RuntimeException(
                'Эмуляция неудачной отправки в Telegram (TELEGRAM_SIMULATE_SEND_FAILURE=1).'
            );
        }

        $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $botToken);

        $response = $this->httpClient->request('POST', $url, [
            'json' => [
                'chat_id' => $chatId,
                'text' => $text,
            ],
            'timeout' => 30,
        ]);

        $this->assertTelegramOkResponse($response);
    }

    private function assertTelegramOkResponse(ResponseInterface $response): void
    {
        $status = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($status !== 200 || !($data['ok'] ?? false)) {
            $description = is_string($data['description'] ?? null) ? $data['description'] : 'Telegram API error';
            throw new \RuntimeException($description);
        }
    }
}
