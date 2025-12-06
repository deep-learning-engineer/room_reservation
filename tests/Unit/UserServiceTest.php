<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private $userRepositoryMock;
    private $validatorMock;

    #[Override]
    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->validatorMock = $this->createMock(ValidatorInterface::class);
        $this->userService = new UserService($this->userRepositoryMock, $this->validatorMock);
    }

    public function testCreateUserSuccess(): void
    {
        $email = 'test@example.com';
        $name = 'John Doe';
        $phone = '79123456789';

        $this->validatorMock->method('validate')->willReturn(new ConstraintViolationList());

        $this->userRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $user = $this->userService->createUser($email, $name, $phone);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($phone, $user->getPhone());
    }

    public function testCreateUserWithValidationErrors(): void
    {
        $violations = new ConstraintViolationList([
            $this->createConstraintViolation('Invalid email', 'email'),
            $this->createConstraintViolation('Invalid phone format', 'phone'),
        ]);

        $this->validatorMock->method('validate')->willReturn($violations);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email');

        $this->userService->createUser('invalid-email', 'John Doe', '123');
    }

    public function testFindUserByPhone(): void
    {
        $phone = '79123456789';
        $expectedUser = new User();

        $this->userRepositoryMock->method('findByPhone')
            ->with($phone)
            ->willReturn($expectedUser);

        $user = $this->userService->findUserByPhone($phone);
        $this->assertSame($expectedUser, $user);
    }

    public function testFindUserByEmail(): void
    {
        $email = 'test@example.com';
        $expectedUser = new User();

        $this->userRepositoryMock->method('findByEmail')
            ->with($email)
            ->willReturn($expectedUser);

        $user = $this->userService->findUserByEmail($email);
        $this->assertSame($expectedUser, $user);
    }

    private function createConstraintViolation(string $message, string $property): ConstraintViolation
    {
        return new ConstraintViolation(
            $message,
            null,
            [],
            null,
            $property,
            null
        );
    }
}
