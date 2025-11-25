<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Service\UserService;
use InvalidArgumentException;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $userServiceMock;

    #[Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userServiceMock = $this->createMock(UserService::class);
        static::getContainer()->set(UserService::class, $this->userServiceMock);
    }

    public function testCreateUserSuccess(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'name' => 'John Doe',
            'phone' => '79123456789',
            'password' => '12345',
        ];

        $mockUser = $this->createMock(User::class);
        $mockUser->method('getId')->willReturn(1);
        $mockUser->method('getEmail')->willReturn('test@example.com');
        $mockUser->method('getName')->willReturn('John Doe');
        $mockUser->method('getPhone')->willReturn('79123456789');

        $this->userServiceMock
            ->expects($this->once())
            ->method('createUser')
            ->with('test@example.com', 'John Doe', '79123456789')
            ->willReturn($mockUser);

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $user = json_decode($responseData['user'], true);

        $this->assertEquals('test@example.com', $user['email']);
        $this->assertEquals('John Doe', $user['name']);
        $this->assertEquals('79123456789', $user['phone']);
    }

    public function testCreateUserMissingFields(): void
    {
        $userData = [
            'email' => 'test@example.com',
            // missing name and phone, password
        ];

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateUserValidationError(): void
    {
        $userData = [
            'email' => 'invalid-email',
            'name' => 'John Doe',
            'phone' => '79123456789',
            'password' => '12345',
        ];

        $this->userServiceMock
            ->method('createUser')
            ->willThrowException(new InvalidArgumentException('Invalid email format'));

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateUserInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }
}
