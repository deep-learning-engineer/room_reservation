<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Booking;
use App\Entity\User;
use App\Service\BookingService;
use App\Service\UserService;
use DateTime;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BookingControllerTest extends WebTestCase
{
    private $client;
    private $bookingServiceMock;
    private $userServiceMock;

    #[Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->bookingServiceMock = $this->createMock(BookingService::class);
        $this->userServiceMock = $this->createMock(UserService::class);

        $container = static::getContainer();
        $container->set(BookingService::class, $this->bookingServiceMock);
        $container->set(UserService::class, $this->userServiceMock);
    }

    public function testCreateBookingSuccess(): void
    {
        $bookingData = [
            'phone' => '79123456789',
            'house_id' => 1,
            'comment' => 'Test booking comment',
        ];

        $mockUser = $this->createMock(User::class);
        $mockUser->method('getId')->willReturn(1);

        $mockBooking = $this->createMock(Booking::class);
        $mockBooking->method('getId')->willReturn(1);
        $mockBooking->method('getComment')->willReturn('Test booking comment');
        $mockBooking->method('getStatus')->willReturn('confirmed');
        $mockBooking->method('getCreatedAt')->willReturn(new DateTime('2023-01-01 12:00:00'));

        $this->userServiceMock
            ->expects($this->once())
            ->method('findUserByPhone')
            ->with('79123456789')
            ->willReturn($mockUser);

        $this->bookingServiceMock
            ->expects($this->once())
            ->method('createBooking')
            ->with($mockUser, 1, 'Test booking comment')
            ->willReturn($mockBooking);

        $this->client->request(
            'POST',
            '/api/booking',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($bookingData)
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('booking', $responseData);
    }

    public function testCreateBookingUserNotFound(): void
    {
        $bookingData = [
            'phone' => '79999999999',
            'house_id' => 1,
            'comment' => 'Test booking comment',
        ];

        $this->userServiceMock
            ->method('findUserByPhone')
            ->with('79999999999')
            ->willReturn(null);

        $this->client->request(
            'POST',
            '/api/booking',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($bookingData)
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateBookingMissingFields(): void
    {
        $bookingData = [
            'phone' => '79123456789',
        ];

        $this->client->request(
            'POST',
            '/api/booking',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($bookingData)
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateBookingCommentSuccess(): void
    {
        $bookingId = 1;
        $updateData = [
            'comment' => 'Updated booking comment',
        ];

        $this->bookingServiceMock
            ->method('updateBookingComment')
            ->with($bookingId, 'Updated booking comment')
            ->willReturn(true);

        $this->client->request(
            'PUT',
            '/api/booking/' . $bookingId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateBookingCommentNotFound(): void
    {
        $bookingId = 999;
        $updateData = [
            'comment' => 'Updated booking comment',
        ];

        $this->bookingServiceMock
            ->method('updateBookingComment')
            ->with($bookingId, 'Updated booking comment')
            ->willReturn(false);

        $this->client->request(
            'PUT',
            '/api/booking/' . $bookingId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
