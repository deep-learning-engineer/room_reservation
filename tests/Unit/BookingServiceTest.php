<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookingServiceTest extends TestCase
{
    private BookingService $bookingService;
    private $bookingRepositoryMock;
    private $houseRepositoryMock;
    private $entityManagerMock;
    private $validatorMock;

    #[Override]
    protected function setUp(): void
    {
        $this->bookingRepositoryMock = $this->createMock(BookingRepository::class);
        $this->houseRepositoryMock = $this->createMock(HouseRepository::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->validatorMock = $this->createMock(ValidatorInterface::class);

        $this->bookingService = new BookingService(
            $this->bookingRepositoryMock,
            $this->houseRepositoryMock,
            $this->entityManagerMock,
            $this->validatorMock
        );
    }

    public function testCreateBookingSuccess(): void
    {
        $user = $this->createMock(User::class);
        $houseId = 1;
        $comment = 'Test booking comment';

        $house = $this->createMock(House::class);
        $house->method('getId')->willReturn($houseId);
        $house->method('getIsAvailable')->willReturn(true);
        $house->expects($this->once())->method('setIsAvailable')->with(false);

        $this->houseRepositoryMock
            ->method('find')
            ->with($houseId)
            ->willReturn($house);

        $this->validatorMock
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManagerMock->expects($this->once())->method('beginTransaction');
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(Booking::class));
        $this->entityManagerMock->expects($this->once())->method('flush');
        $this->entityManagerMock->expects($this->once())->method('commit');

        $booking = $this->bookingService->createBooking($user, $houseId, $comment);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertSame($user, $booking->getUser());
        $this->assertSame($house, $booking->getHouse());
        $this->assertEquals($comment, $booking->getComment());
        $this->assertEquals('confirmed', $booking->getStatus());
    }

    public function testCreateBookingHouseNotFound(): void
    {
        $user = $this->createMock(User::class);
        $houseId = 999;
        $comment = 'Test booking comment';

        $this->houseRepositoryMock
            ->method('find')
            ->with($houseId)
            ->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('House not found');

        $this->bookingService->createBooking($user, $houseId, $comment);
    }

    public function testCreateBookingHouseNotAvailable(): void
    {
        $user = $this->createMock(User::class);
        $houseId = 1;
        $comment = 'Test booking comment';

        $house = $this->createMock(House::class);
        $house->method('getIsAvailable')->willReturn(false);

        $this->houseRepositoryMock
            ->method('find')
            ->with($houseId)
            ->willReturn($house);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('House is not available for booking');

        $this->bookingService->createBooking($user, $houseId, $comment);
    }

    public function testCreateBookingTransactionRollbackOnException(): void
    {
        $user = $this->createMock(User::class);
        $houseId = 1;
        $comment = 'Test booking comment';

        $house = $this->createMock(House::class);
        $house->method('getIsAvailable')->willReturn(true);

        $this->houseRepositoryMock
            ->method('find')
            ->with($houseId)
            ->willReturn($house);

        $this->validatorMock
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManagerMock->expects($this->once())->method('beginTransaction');
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush')
            ->willThrowException(new Exception('DB error'));
        $this->entityManagerMock->expects($this->once())->method('rollback');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('DB error');

        $this->bookingService->createBooking($user, $houseId, $comment);
    }

    public function testUpdateBookingCommentSuccess(): void
    {
        $bookingId = 1;
        $newComment = 'Updated booking comment';

        $booking = $this->createMock(Booking::class);
        $booking->expects($this->once())->method('setComment')->with($newComment);

        $this->bookingRepositoryMock
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->validatorMock
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManagerMock->expects($this->once())->method('flush');

        $result = $this->bookingService->updateBookingComment($bookingId, $newComment);

        $this->assertTrue($result);
    }

    public function testUpdateBookingCommentNotFound(): void
    {
        $bookingId = 999;
        $newComment = 'Updated booking comment';

        $this->bookingRepositoryMock
            ->method('find')
            ->with($bookingId)
            ->willReturn(null);

        $result = $this->bookingService->updateBookingComment($bookingId, $newComment);

        $this->assertFalse($result);
    }

    public function testUpdateBookingCommentTrimsInput(): void
    {
        $bookingId = 1;
        $commentWithSpaces = '  Updated comment with spaces   ';
        $expectedTrimmedComment = 'Updated comment with spaces';

        $booking = $this->createMock(Booking::class);
        $booking->expects($this->once())
                ->method('setComment')
                ->with($expectedTrimmedComment);

        $this->bookingRepositoryMock
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->validatorMock
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManagerMock->expects($this->once())->method('flush');

        $result = $this->bookingService->updateBookingComment($bookingId, $commentWithSpaces);

        $this->assertTrue($result);
    }
}
