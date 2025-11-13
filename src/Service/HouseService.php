<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\HouseRepository;

class HouseService
{
    private HouseRepository $houseRepository;

    public function __construct(HouseRepository $houseRepository)
    {
        $this->houseRepository = $houseRepository;
    }

    public function getAvailableHouses(): array
    {
        return $this->houseRepository->findAvailableHouses();
    }

    public function getHouseById(int $houseId): ?array
    {
        $house = $this->houseRepository->find($houseId);

        if (!$house) {
            return null;
        }

        return [
            'id' => $house->getId(),
            'name' => $house->getName(),
            'description' => $house->getDescription(),
            'price' => $house->getPrice(),
            'is_available' => $house->getIsAvailable(),
        ];
    }
}
