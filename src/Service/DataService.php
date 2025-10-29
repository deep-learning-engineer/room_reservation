<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

class DataService
{
    private string $projectDir;
    private Filesystem $filesystem;

    public function __construct(string $projectDir, Filesystem $filesystem)
    {
        $this->projectDir = $projectDir;
        $this->filesystem = $filesystem;
    }

    /**
     * Получение списка свободных помещений
     */
    public function getAvailableHouses(): array
    {
        $houses = $this->readCsv('houses.csv');

        return array_filter($houses, function($house) {
            return isset($house['available']) && $house['available'] === '1';
        });
    }

    /**
     * Бронирование помещения
     */
    public function bookHouse(string $houseId, string $phone, string $comment): array
    {
        $houses = $this->readCsv('houses.csv');
        $targetHouse = null;
        $houseIndex = null;

        foreach ($houses as $index => $house) {
            if ($house['id'] === $houseId) {
                $targetHouse = $house;
                $houseIndex = $index;
                break;
            }
        }

        if (!$targetHouse) {
            return [
                'success' => false, 
                'error' => 'House not found',
                'status_code' => 404
            ];
        }

        if ($targetHouse['available'] !== '1') {
            return [
                'success' => false,
                'error' => 'House is not available',
                'status_code' => 409
            ];
        }

        if (empty(trim($comment))) {
            return [
                'success' => false,
                'error' => 'Comment cannot be empty',
                'status_code' => 400
            ];
        }

        if (!$this->isValidPhone($phone)) {
            return [
                'success' => false,
                'error' => 'Invalid phone number format',
                'status_code' => 400
            ];
        }

        $houses[$houseIndex]['available'] = '0';
        if ($this->writeCsvData('houses.csv', $houses)) {
            $booking = [
                'id' => uniqid(),
                'house_id' => $houseId,
                'phone' => $phone,
                'comment' => trim($comment),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->appendToCsv('bookings.csv', $booking)) {
                return [
                    'success' => true,
                    'booking_id' => $booking['id'],
                    'message' => 'House booked successfully'
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Failed to book house',
            'status_code' => 500
        ];
    }

    /**
     * Валидация номера телефона
     */
    private function isValidPhone(string $phone): bool
    {
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
        return strlen($cleanPhone) >= 10;
    }

    /**
     * Обновление комментария бронирования
     */
    public function updateBookingComment(string $bookingId, string $newComment): bool
    {
        $bookings = $this->readCsv('bookings.csv');
        $updated = false;

        foreach ($bookings as &$booking) {
            if ($booking['id'] === $bookingId) {
                $booking['comment'] = $newComment;
                $updated = true;
                break;
            }
        }

        if ($updated) {
            return $this->writeCsvData('bookings.csv', $bookings);
        }

        return false;
    }

    /**
     * Получение информации о конкретном доме
     */
    public function getHouseById(string $houseId): ?array
    {
        $houses = $this->readCsv('houses.csv');
        
        foreach ($houses as $house) {
            if ($house['id'] === $houseId) {
                return $house;
            }
        }

        return null;
    }

    private function readCsv(string $filename): array
    {
        $filepath = $this->projectDir . '/data/' . $filename;
        
        if (!$this->filesystem->exists($filepath)) {
            return [];
        }

        $data = [];
        if (($handle = fopen($filepath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Добавление новой записи в CSV
     */
    private function appendToCsv(string $filename, array $newRow): bool
    {
        $filepath = $this->projectDir . '/data/' . $filename;
        $this->filesystem->mkdir(dirname($filepath));

        $fileExists = $this->filesystem->exists($filepath);
        
        if (($handle = fopen($filepath, $fileExists ? 'a' : 'w')) !== false) {
            if (!$fileExists) {
                fputcsv($handle, array_keys($newRow));
            }
            
            fputcsv($handle, $newRow);
            fclose($handle);
            return true;
        }

        return false;
    }

    /**
     * Запись данных
     */
    private function writeCsvData(string $filename, array $data): bool
    {
        $filepath = $this->projectDir . '/data/' . $filename;
        
        if (($handle = fopen($filepath, 'w')) !== false) {
            if (!empty($data)) {
                fputcsv($handle, array_keys($data[0]));
                
                foreach ($data as $row) {
                    fputcsv($handle, $row);
                }
            }
            fclose($handle);
            return true;
        }

        return false;
    }
}