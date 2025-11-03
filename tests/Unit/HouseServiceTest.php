<?php
namespace App\Tests\Unit\Service;

use App\Entity\House;
use App\Repository\HouseRepository;
use App\Service\HouseService;
use PHPUnit\Framework\TestCase;

class HouseServiceTest extends TestCase
{
    private HouseService $houseService;
    private $houseRepositoryMock;

    protected function setUp(): void
    {
        $this->houseRepositoryMock = $this->createMock(HouseRepository::class);
        $this->houseService = new HouseService($this->houseRepositoryMock);
    }

    public function testGetAvailableHouses(): void
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
                ->setIsAvailable(true)
        ];

        $this->houseRepositoryMock
            ->expects($this->once())
            ->method('findAvailableHouses')
            ->willReturn($mockHouses);

        $result = $this->houseService->getAvailableHouses();

        $this->assertCount(2, $result);
        $this->assertSame($mockHouses, $result);
        $this->assertInstanceOf(House::class, $result[0]);
        $this->assertEquals('House 1', $result[0]->getName());
        $this->assertEquals(100.0, $result[0]->getPrice());
        $this->assertEquals(true, $result[0]->getIsAvailable());
    }

    public function testGetAvailableHousesWhenEmpty(): void
    {
        $this->houseRepositoryMock
            ->expects($this->once())
            ->method('findAvailableHouses')
            ->willReturn([]);

        $result = $this->houseService->getAvailableHouses();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetHouseById(): void
    {
        $houseId = 1;
        
        $mockHouse = $this->createMock(House::class);
        $mockHouse->method('getId')->willReturn($houseId);
        $mockHouse->method('getName')->willReturn('Test House');
        $mockHouse->method('getDescription')->willReturn('Test Description');
        $mockHouse->method('getPrice')->willReturn(200.0);
        $mockHouse->method('getIsAvailable')->willReturn(true);

        $this->houseRepositoryMock
            ->method('find')
            ->with($houseId)
            ->willReturn($mockHouse);

        $result = $this->houseService->getHouseById($houseId);

        $this->assertIsArray($result);
        $this->assertEquals([
            'id' => $houseId,
            'name' => 'Test House',
            'description' => 'Test Description',
            'price' => 200.0,
            'is_available' => true
        ], $result);
    }

    public function testGetHouseByIdNotFound(): void
    {
        $houseId = 999;

        $this->houseRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($houseId)
            ->willReturn(null);

        $result = $this->houseService->getHouseById($houseId);

        $this->assertNull($result);
    }
}