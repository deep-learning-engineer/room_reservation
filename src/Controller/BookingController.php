<?php

namespace App\Controller;

use App\Service\DataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController
{
    private DataService $dataService;

    public function __construct(DataService $dataService)
    {
        $this->dataService = $dataService;
    }

    // Создание заявки на бронирование
    #[Route('/api/booking', name: 'api_booking_create', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['phone']) || !isset($data['house_id']) || !isset($data['comment'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Missing required fields: phone, house_id, comment'
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->dataService->bookHouse(
            $data['house_id'], 
            $data['phone'], 
            $data['comment']
        );

        $statusCode = $result['status_code'] ?? 
                    ($result['success'] ? Response::HTTP_CREATED : Response::HTTP_INTERNAL_SERVER_ERROR);

        return new JsonResponse($result, $statusCode);
    }

    // Изменение комментария бронирования
    #[Route('/api/booking/{id}', name: 'api_booking_update', methods: ['PUT'])]
    public function updateBooking(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['comment'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Comment field is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($this->dataService->updateBookingComment($id, $data['comment'])) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Booking comment updated successfully'
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'error' => 'Booking not found or update failed'
        ], Response::HTTP_NOT_FOUND);
    }
}