<?php
namespace App\Controller;

use App\Service\BookingService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingController extends AbstractController
{
    private BookingService $bookingService;
    private UserService $userService;

    public function __construct(BookingService $bookingService, UserService $userService)
    {
        $this->bookingService = $bookingService;
        $this->userService = $userService;
    }
    
    // Создание заявки на бронирование
    #[Route('/api/booking', name: 'api_booking_create', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['phone']) || !isset($data['house_id']) || !isset($data['comment'])) {
            throw new BadRequestHttpException('Missing required fields: phone, house_id, comment');
        }

        $user = $this->userService->findUserByPhone($data['phone']);
        if (!$user) {
            throw new NotFoundHttpException('User not found. Please create user first.');
        }

        try {
            $booking = $this->bookingService->createBooking(
                $user,
                (int)$data['house_id'],
                $data['comment']
            );

            return new JsonResponse([
                'success' => true,
                'booking' => [
                    'id' => $booking->getId(),
                    'user_id' => $user->getId(),
                    'house_id' => $data['house_id'],
                    'comment' => $booking->getComment(),
                    'status' => $booking->getStatus(),
                    'created_at' => $booking->getCreatedAt()->format('Y-m-d H:i:s')
                ],
                'message' => 'Booking created successfully'
            ], Response::HTTP_CREATED);

        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    // Изменение комментария бронирования
    #[Route('/api/booking/{id}', name: 'api_booking_update', methods: ['PUT'])]
    public function updateBooking(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['comment'])) {
            throw new BadRequestHttpException('Comment field is required');
        }

        try {
            $result = $this->bookingService->updateBookingComment($id, $data['comment']);
            
            if (!$result) {
                throw new NotFoundHttpException('Booking not found');
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Booking comment updated successfully'
            ]);

        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}