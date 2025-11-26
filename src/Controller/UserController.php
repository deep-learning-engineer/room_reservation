<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
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
    #[OA\Post(
        summary: 'Register a new user',
        description: 'Creates a new user account with the provided information',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['email', 'name', 'phone', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'phone', type: 'string', example: '79123456789'),
                    new OA\Property(property: 'password', type: 'string', example: 'securePassword123'),
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'User created successfully',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'user', ref: new Model(type: User::class, groups: ['api'])),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request - missing or invalid fields'
    )]
    #[OA\Tag(name: 'Authentication')]
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
    #[OA\Get(
        summary: 'Get current user profile',
        description: 'Retrieves the profile information of the currently authenticated user'
    )]
    #[OA\Response(
        response: 200,
        description: 'User profile data',
        content: new OA\JsonContent(ref: new Model(type: User::class, groups: ['api']))
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized - user not logged in'
    )]
    #[OA\Tag(name: 'User')]
    public function profile(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->serializer->serialize($user, 'json', ['groups' => 'api']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
