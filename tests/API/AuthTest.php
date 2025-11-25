<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    #[Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        assert($em instanceof EntityManagerInterface);
        assert($hasher instanceof UserPasswordHasherInterface);

        $this->entityManager = $em;
        $this->passwordHasher = $hasher;

        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    private function createTestUser(string $phone, string $password): User
    {
        if ('' === $phone) {
            throw new InvalidArgumentException('Phone cannot be empty in test');
        }

        $user = new User();
        $user->setEmail('test_' . $phone . '@example.com');
        $user->setName('Test User ' . $phone);
        $user->setPhone($phone);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function safeJsonEncode(array $data): string
    {
        $json = json_encode($data);
        if (false === $json) {
            throw new RuntimeException('Failed to encode JSON');
        }

        return $json;
    }

    private function safeJsonDecode(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException('Failed to decode JSON or result is not an array');
        }

        return $data;
    }

    public function testLoginSuccess(): void
    {
        $this->createTestUser('79001234567', '12345');

        $loginData = ['phone' => '79001234567', 'password' => '12345'];

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->safeJsonEncode($loginData)
        );

        $this->assertResponseIsSuccessful();

        $data = $this->safeJsonDecode((string) $this->client->getResponse()->getContent());

        if (!isset($data['email'])) {
            $this->fail('Response does not contain "email"');
        }

        $this->assertEquals('test_79001234567@example.com', $data['email']);
    }

    public function testLoginFailureWrongPassword(): void
    {
        $this->createTestUser('79001234567', '12345');

        $loginData = ['phone' => '79001234567', 'password' => 'wrong'];

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->safeJsonEncode($loginData)
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginFailureUserNotFound(): void
    {
        $loginData = ['phone' => '79999999999', 'password' => '12345'];

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->safeJsonEncode($loginData)
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProfileAccessUnauthorized(): void
    {
        $this->client->request('GET', '/api/profile');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testProfileAccessAuthorized(): void
    {
        $user = $this->createTestUser('79005555555', '12345');
        $this->client->loginUser($user);

        $this->client->request('GET', '/api/profile');
        $this->assertResponseIsSuccessful();

        $data = $this->safeJsonDecode((string) $this->client->getResponse()->getContent());

        if (!isset($data['email'])) {
            $this->fail('Response does not contain "email"');
        }

        $this->assertEquals($user->getEmail(), $data['email']);
    }

    public function testLogout(): void
    {
        $user = $this->createTestUser('79006666666', '12345');
        $this->client->loginUser($user);

        $this->client->request('POST', '/api/logout');
        $this->assertResponseStatusCodeSame(302);

        $this->client->request('GET', '/api/profile');
        $this->assertResponseStatusCodeSame(401);
    }
}
