<?php
namespace App\Service;

use App\Entity\Booking;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookingService
{
    private BookingRepository $bookingRepository;
    private HouseRepository $houseRepository;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(
        BookingRepository $bookingRepository,
        HouseRepository $houseRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->houseRepository = $houseRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public function createBooking(User $user, int $houseId, string $comment): Booking
    {
        $house = $this->houseRepository->find($houseId);
        if (!$house) {
            throw new \InvalidArgumentException('House not found');
        }

        if (!$house->getIsAvailable()) {
            throw new \InvalidArgumentException('House is not available for booking');
        }

        $booking = new Booking();
        $booking->setUser($user);
        $booking->setHouse($house);
        $booking->setComment(trim($comment));
        $booking->setStatus('confirmed');

        $errors = $this->validator->validate($booking);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->beginTransaction();
        try {
            $house->setIsAvailable(false);
            
            $this->entityManager->persist($booking);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $booking;
    }

    public function updateBookingComment(int $bookingId, string $comment): bool
    {
        $booking = $this->bookingRepository->find($bookingId);
        if (!$booking) {
            return false;
        }

        $booking->setComment(trim($comment));

        $errors = $this->validator->validate($booking);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->flush();
        return true;
    }

    public function getUserBookings(int $userId): array
    {
        return $this->bookingRepository->findByUser($userId);
    }
}