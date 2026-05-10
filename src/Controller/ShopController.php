<?php

namespace App\Controller;

use App\Repository\ShopRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShopController
{
    #[Route('/shops', name: 'shops_list', methods: ['GET'])]
    public function list(ShopRepository $repo): JsonResponse
    {
        $shops = $repo->findAllOrderedById();

        return new JsonResponse(
            array_map(static fn ($shop) => [
                'id' => $shop->getId(),
                'name' => $shop->getName(),
            ], $shops),
            Response::HTTP_OK
        );
    }
}

