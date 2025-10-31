<?php

namespace App\Controller;

use App\Service\DataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HouseController extends AbstractController
{
    private DataService $dataService;

    public function __construct(DataService $dataService)
    {
        $this->dataService = $dataService;
    }

    // Получение списка свободных помещений в виде HTML страницы
    #[Route('/houses', name: 'houses', methods: ['GET'])]
    public function getAvailableHouses(): Response
    {
        $availableHouses = $this->dataService->getAvailableHouses();

        return $this->render('houses/list.html.twig', [
            'houses' => $availableHouses,
        ]);
    }

    // Получение списка свободных помещений
    #[Route('/api/houses', name: 'api_houses_all', methods: ['GET'])]
    public function getAvailableHousesAPI(): JsonResponse
    {
        $houses = $this->dataService->getAvailableHouses();
        
        return new JsonResponse([
            'success' => true,
            'houses' => $houses,
            'total' => count($houses)
        ]);
    }

    // Получение информации о конкретном доме
    #[Route('/api/houses/{id}', name: 'api_house', methods: ['GET'])]
    public function getHouse(string $id): JsonResponse
    {
        $house = $this->dataService->getHouseById($id);
        
        if (!$house) {
            throw new NotFoundHttpException('House not found');
        }

        return new JsonResponse([
            'success' => true,
            'house' => $house
        ]);
    }
}