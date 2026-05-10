<?php

namespace App\Controller;

use App\Entity\Order;
use App\Http\Request\CreateOrderRequest;
use App\Message\OrderCreatedMessage;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OrderController
{
    #[Route('/shops/{shopId}/orders', name: 'orders_create', methods: ['POST'])]
    public function create(
        int $shopId,
        Request $request,
        ValidatorInterface $validator,
        MessageBusInterface $bus,
        EntityManagerInterface $em,
    ): JsonResponse {
        $payload = json_decode($request->getContent() ?: '', true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $dto = new CreateOrderRequest(
            $payload['number'] ?? null,
            $payload['total'] ?? null,
            $payload['customerName'] ?? null,
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

        $now = new \DateTimeImmutable('now');
        $order = new Order(
            shopId: (string) $shopId,
            number: $dto->numberString(),
            total: $dto->totalNumeric(),
            customerName: $dto->customerNameString(),
        );

        try {
            $em->persist($order);
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            return new JsonResponse(
                ['error' => 'Order number already exists'],
                Response::HTTP_CONFLICT
            );
        }

        $id = (int) $order->getId();

        $bus->dispatch(new OrderCreatedMessage(
            orderId: $id,
            shopId: (string) $shopId,
            number: $dto->numberString(),
            total: $dto->totalNumeric(),
            customerName: $dto->customerNameString(),
            createdAt: $now->format(DATE_ATOM),
        ));

        return new JsonResponse(
            [
                'id' => $id,
                'shopId' => (string) $shopId,
                'number' => $dto->numberString(),
                'total' => $dto->totalNumeric(),
                'customerName' => $dto->customerNameString(),
                'createdAt' => $now->format(DATE_ATOM),
            ],
            Response::HTTP_CREATED
        );
    }
}

