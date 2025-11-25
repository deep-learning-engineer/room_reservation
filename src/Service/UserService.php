<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService
{
    private UserRepository $userRepository;
    private ValidatorInterface $validator;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        UserRepository $userRepository,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
    ) {
        $this->userRepository = $userRepository;
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;
    }

    public function createUser(string $email, string $name, string $phone, string $plainPassword): User
    {
        $user = new User();
        $user->setEmail(trim($email));
        $user->setName(trim($name));
        $cleanPhone = trim($phone);

        if ('' === $cleanPhone) {
            throw new InvalidArgumentException('Phone cannot be empty');
        }

        $user->setPhone($cleanPhone);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plainPassword
        );
        $user->setPassword($hashedPassword);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new InvalidArgumentException((string) $errors);
        }

        try {
            $this->userRepository->save($user);

            return $user;
        } catch (UniqueConstraintViolationException $e) {
            $field = $this->getViolatedFieldFromException($e);
            throw new InvalidArgumentException("User with this $field already exists");
        }
    }

    public function findUserByPhone(string $phone): ?User
    {
        return $this->userRepository->findByPhone($phone);
    }

    private function getViolatedFieldFromException(UniqueConstraintViolationException $e): string
    {
        $message = $e->getMessage();

        if (false !== strpos($message, 'email') || false !== strpos($message, 'UNIQ_EMAIL')) {
            return 'email';
        }

        if (false !== strpos($message, 'phone') || false !== strpos($message, 'UNIQ_PHONE')) {
            return 'phone';
        }

        return 'email or phone';
    }
}
