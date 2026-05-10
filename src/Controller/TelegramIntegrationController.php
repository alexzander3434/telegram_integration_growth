<?php

namespace App\Controller;

use App\Entity\TelegramIntegration;
use App\Entity\TelegramSendStatus;
use App\Http\Request\TelegramConnectRequest;
use App\Repository\TelegramIntegrationRepository;
use App\Repository\TelegramSendLogRepository;
use App\Service\TelegramIntegrationCache;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TelegramIntegrationController
{
    #[Route('/shops/{shopId}/telegram/status', name: 'telegram_integration_status', methods: ['GET'])]
    public function status(
        int $shopId,
        TelegramIntegrationRepository $integrationRepo,
        TelegramSendLogRepository $sendLogRepo,
    ): JsonResponse {
        $integration = $integrationRepo->findOneByShopId($shopId);
        if ($integration === null) {
            return new JsonResponse([
                'enabled' => false,
                'chatId' => null,
                'lastSentAt' => null,
                'sentCount' => 0,
                'failedCount' => 0,
            ]);
        }

        $shopIdStr = (string) $shopId;
        $since = new \DateTimeImmutable('-7 days');
        $sentCount = $sendLogRepo->countByShopAndStatusSince($shopIdStr, TelegramSendStatus::SENT, $since);
        $failedCount = $sendLogRepo->countByShopAndStatusSince($shopIdStr, TelegramSendStatus::FAILED, $since);
        $lastSentAt = $sendLogRepo->findLastSuccessfulSentAt($shopIdStr);

        return new JsonResponse([
            'enabled' => $integration->isEnabled(),
            'chatId' => self::maskChatId($integration->getChatId()),
            'lastSentAt' => $lastSentAt?->format(DATE_ATOM),
            'sentCount' => $sentCount,
            'failedCount' => $failedCount,
        ]);
    }

    #[Route('/shops/{shopId}/telegram/connect', name: 'telegram_integration_connect', methods: ['POST'])]
    public function connect(
        int $shopId,
        Request $request,
        ValidatorInterface $validator,
        TelegramIntegrationRepository $repo,
        EntityManagerInterface $em,
        TelegramIntegrationCache $cache,
    ): JsonResponse {
        $payload = json_decode($request->getContent() ?: '', true);
        if (!is_array($payload)) {
            return new JsonResponse(
                ['error' => 'Invalid JSON payload'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $dto = new TelegramConnectRequest(
            $payload['botToken'] ?? null,
            $payload['chatId'] ?? null,
            $payload['enabled'] ?? null,
        );

        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => (string) $violation->getPropertyPath(),
                    'message' => (string) $violation->getMessage(),
                ];
            }

            return new JsonResponse(
                ['error' => 'Validation failed', 'details' => $errors],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($repo->findOneByShopId($shopId) !== null) {
            return new JsonResponse(
                ['error' => 'Telegram integration for this shop already exists'],
                Response::HTTP_CONFLICT
            );
        }

        $integration = new TelegramIntegration(
            shopId: $shopId,
            botToken: $dto->botTokenString(),
            chatId: $dto->chatIdString(),
            enabled: $dto->enabledBool(),
        );

        try {
            $em->persist($integration);
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            return new JsonResponse(
                ['error' => 'Telegram integration for this shop already exists'],
                Response::HTTP_CONFLICT
            );
        }

        $cache->upsertShopIntegration(
            shopId: (string) $integration->getShopId(),
            botToken: $integration->getBotToken(),
            chatId: $integration->getChatId(),
            enabled: $integration->isEnabled(),
        );

        return new JsonResponse(
            [
                'id' => $integration->getId(),
                'shopId' => $integration->getShopId(),
                'chatId' => $integration->getChatId(),
                'enabled' => $integration->isEnabled(),
                'createdAt' => $integration->getCreatedAt()->format(DATE_ATOM),
                'updatedAt' => $integration->getUpdatedAt()->format(DATE_ATOM),
            ],
            Response::HTTP_CREATED
        );
    }

    private static function maskChatId(string $chatId): string
    {
        $chatId = trim($chatId);
        $len = strlen($chatId);
        if ($len <= 2) {
            return str_repeat('*', max($len, 2));
        }
        if ($len <= 6) {
            return substr($chatId, 0, 1) . str_repeat('*', $len - 2) . substr($chatId, -1);
        }

        return substr($chatId, 0, 2) . '***' . substr($chatId, -2);
    }
}

