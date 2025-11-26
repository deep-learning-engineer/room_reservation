<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\BookingService;
use App\Service\UserService;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
#[OA\Tag(name: 'Bookings')]
class BookingController extends AbstractController
{
    private BookingService $bookingService;
    private UserService $userService;

    public function __construct(BookingService $bookingService, UserService $userService)
    {
        $this->bookingService = $bookingService;
        $this->userService = $userService;
    }

    #[Route('/booking', name: 'api_booking_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create booking',
        description: 'Create a new booking for a house'
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: 'object',
            required: ['phone', 'house_id', 'comment'],
            properties: [
                new OA\Property(property: 'phone', type: 'string', example: '79123456789'),
                new OA\Property(property: 'house_id', type: 'integer', example: 1),
                new OA\Property(property: 'comment', type: 'string', example: 'I would like to book'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Booking created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean'),
                new OA\Property(property: 'booking', type: 'object'),
                new OA\Property(property: 'message', type: 'string'),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing required fields or invalid data'
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found'
    )]
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
                (int) $data['house_id'],
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
                    'created_at' => $booking->getCreatedAt()->format('Y-m-d H:i:s'),
                ],
                'message' => 'Booking created successfully',
            ], Response::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    #[Route('/booking/{id}', name: 'api_booking_update', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Update booking comment',
        description: 'Update the comment of an existing booking'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
        description: 'Booking ID'
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: 'object',
            required: ['comment'],
            properties: [
                new OA\Property(property: 'comment', type: 'string', example: 'Updated comment about the booking'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Booking comment updated successfully'
    )]
    #[OA\Response(
        response: 400,
        description: 'Comment field is required'
    )]
    #[OA\Response(
        response: 404,
        description: 'Booking not found'
    )]
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
                'message' => 'Booking comment updated successfully',
            ]);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}
