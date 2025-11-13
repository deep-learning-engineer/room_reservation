<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use InvalidArgumentException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService
{
    private UserRepository $userRepository;
    private ValidatorInterface $validator;

    public function __construct(UserRepository $userRepository, ValidatorInterface $validator)
    {
        $this->userRepository = $userRepository;
        $this->validator = $validator;
    }

    public function createUser(string $email, string $name, string $phone): User
    {
        $user = new User();
        $user->setEmail(trim($email));
        $user->setName(trim($name));
        $user->setPhone(trim($phone));

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

    public function findUserByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
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
