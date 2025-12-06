<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\House;
use App\Service\HouseService;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HouseControllerTest extends WebTestCase
{
    private $client;
    private $houseServiceMock;

    #[Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->houseServiceMock = $this->createMock(HouseService::class);
        static::getContainer()->set(HouseService::class, $this->houseServiceMock);
    }

    public function testGetAvailableHousesAPI(): void
    {
        $mockHouses = [
            (new House())
                ->setName('House 1')
                ->setDescription('Description 1')
                ->setPrice(100.0)
                ->setIsAvailable(true),
            (new House())
                ->setName('House 2')
                ->setDescription('Description 2')
                ->setPrice(150.0)
                ->setIsAvailable(true),
        ];

        $this->houseServiceMock
            ->method('getAvailableHouses')
            ->willReturn($mockHouses);

        $this->client->request('GET', '/api/houses');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertCount(2, $responseData['houses']);
        $this->assertEquals(2, $responseData['total']);
    }

    public function testGetAvailableHousesEmpty(): void
    {
        $this->houseServiceMock
            ->method('getAvailableHouses')
            ->willReturn([]);

        $this->client->request('GET', '/api/houses');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEmpty($responseData['houses']);
        $this->assertEquals(0, $responseData['total']);
    }

    public function testGetHouseByIdSuccess(): void
    {
        $houseId = 1;
        $houseData = [
            'id' => $houseId,
            'name' => 'Test House',
            'description' => 'Test Description',
            'price' => 200.0,
            'is_available' => true,
        ];

        $this->houseServiceMock
            ->method('getHouseById')
            ->with($houseId)
            ->willReturn($houseData);

        $this->client->request('GET', '/api/houses/' . $houseId);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($houseData, $responseData['house']);
    }

    public function testGetHouseByIdNotFound(): void
    {
        $houseId = 999;

        $this->houseServiceMock
            ->method('getHouseById')
            ->with($houseId)
            ->willReturn(null);

        $this->client->request('GET', '/api/houses/' . $houseId);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
