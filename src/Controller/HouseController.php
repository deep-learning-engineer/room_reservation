<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\HouseService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Houses')]
class HouseController extends AbstractController
{
    private HouseService $houseService;

    public function __construct(HouseService $houseService)
    {
        $this->houseService = $houseService;
    }

    #[Route('api/houses', name: 'api_houses_all', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get available houses',
        description: 'Get list of all available houses for booking'
    )]
    #[OA\Response(
        response: 200,
        description: 'List of available houses',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean'),
                new OA\Property(property: 'houses', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'total', type: 'integer'),
            ]
        )
    )]
    public function getAvailableHousesAPI(): JsonResponse
    {
        $houses = $this->houseService->getAvailableHouses();

        return new JsonResponse([
            'success' => true,
            'houses' => $houses,
            'total' => count($houses),
        ]);
    }

    #[Route('api/houses/{id}', name: 'api_house', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get house details',
        description: 'Get detailed information about a specific house'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
        description: 'House ID'
    )]
    #[OA\Response(
        response: 200,
        description: 'House details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean'),
                new OA\Property(property: 'house', type: 'object'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'House not found'
    )]
    public function getHouse(string $id): JsonResponse
    {
        $house = $this->houseService->getHouseById((int) $id);

        if (!$house) {
            throw new NotFoundHttpException('House not found');
        }

        return new JsonResponse([
            'success' => true,
            'house' => $house,
        ]);
    }
}
