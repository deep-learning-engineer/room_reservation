<?php

namespace App\Controller;

use App\Service\DataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            throw new BadRequestHttpException('Missing required fields: phone, house_id, comment');
        }

        $result = $this->dataService->bookHouse(
            $data['house_id'], 
            $data['phone'], 
            $data['comment']
        );

        if (!$result['success']) {
            throw new UnprocessableEntityHttpException($result['error'] ?? 'Unknown error');
        }

        return new JsonResponse($result, Response::HTTP_CREATED);
    }

    // Изменение комментария бронирования
    #[Route('/api/booking/{id}', name: 'api_booking_update', methods: ['PUT'])]
    public function updateBooking(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['comment'])) {
            throw new BadRequestHttpException('Comment field is required');
        }

        if (!$this->dataService->updateBookingComment($id, $data['comment'])) {
            throw new NotFoundHttpException('Booking not found or update failed');
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Booking comment updated successfully'
        ]);
    }
}