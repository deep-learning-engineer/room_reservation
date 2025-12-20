<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
#[OA\Tag(name: 'Authentication')]
class AuthController extends AbstractController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    #[OA\Post(
        summary: 'User login',
        description: 'Authenticate user and return user data',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                required: ['phone', 'password'],
                properties: [
                    new OA\Property(property: 'phone', type: 'string', example: '79123456789'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful login',
        content: new OA\JsonContent(ref: new Model(type: User::class, groups: ['api']))
    )]
    #[OA\Response(
        response: 401,
        description: 'Invalid credentials'
    )]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'message' => 'missing credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->serializer->serialize($user, 'json', ['groups' => 'api']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    #[OA\Post(
        summary: 'User logout',
        description: 'Log out the current user'
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful logout',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully'),
            ]
        )
    )]
    public function logout(): JsonResponse
    {
        return $this->json(['message' => 'Logged out successfully']);
    }
}
