<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\UserService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{
    private UserService $userService;
    private SerializerInterface $serializer;

    public function __construct(UserService $userService, SerializerInterface $serializer)
    {
        $this->userService = $userService;
        $this->serializer = $serializer;
    }

    #[Route('/api/register', name: 'api_user_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['name']) || !isset($data['phone']) || !isset($data['password'])) {
            throw new BadRequestHttpException('Missing required fields: email, name, phone, password');
        }

        try {
            $user = $this->userService->createUser(
                $data['email'],
                $data['name'],
                $data['phone'],
                $data['password']
            );

            $user_data = $this->serializer->serialize($user, 'json', ['groups' => 'api']);

            return new JsonResponse(['success' => true, 'user' => $user_data], Response::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    #[Route('/api/profile', name: 'api_user_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $user_data = $this->serializer->serialize($user, 'json', ['groups' => 'api']);

        return new JsonResponse($user_data);
    }
}
