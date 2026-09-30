<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class RoomRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        return $this->pdo->query(
            'SELECT rooms.*, room_types.name AS room_type_name
             FROM rooms
             INNER JOIN room_types ON room_types.id = rooms.room_type_id
             ORDER BY rooms.room_number'
        )->fetchAll();
    }

    public function availableCatalog(): array
    {
        $rooms = $this->pdo->query(
            "SELECT
                rooms.id,
                rooms.room_number AS number,
                room_types.name AS category,
                rooms.location,
                rooms.description,
                rooms.capacity,
                room_types.base_price AS price,
                rooms.equipment
             FROM rooms
             INNER JOIN room_types ON room_types.id = rooms.room_type_id
             WHERE rooms.status = 'available'
             ORDER BY rooms.room_number"
        )->fetchAll();

        return array_map(static function (array $room): array {
            $equipment = json_decode((string) $room['equipment'], true);
            $room['equipment'] = is_array($equipment) ? $equipment : [];

            return $room;
        }, $rooms);
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM rooms WHERE id = :id');
        $statement->execute(['id' => $id]);
        $room = $statement->fetch();

        return $room === false ? null : $room;
    }

    public function roomTypes(): array
    {
        return $this->pdo->query('SELECT id, name, description, base_price, max_guests FROM room_types ORDER BY name')->fetchAll();
    }

    public function findRoomType(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, description, base_price, max_guests FROM room_types WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $roomType = $statement->fetch();

        return $roomType === false ? null : $roomType;
    }

    public function updateRoomTypePrice(int $id, float $price): void
    {
        if ($price <= 0) {
            throw new \InvalidArgumentException('El precio debe ser mayor que cero.');
        }
        $statement = $this->pdo->prepare('UPDATE room_types SET base_price = :price WHERE id = :id');
        $statement->execute(['price' => $price, 'id' => $id]);
    }

    public function create(array $data): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO rooms
                (room_type_id, capacity, room_number, location, description, equipment, image_url, status)
             VALUES
                (:room_type_id, :capacity, :room_number, :location, :description, :equipment, :image_url, :status)'
        );
        $statement->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE rooms SET
                room_type_id = :room_type_id,
                capacity = :capacity,
                room_number = :room_number,
                location = :location,
                description = :description,
                equipment = :equipment,
                image_url = :image_url,
                status = :status
             WHERE id = :id'
        );
        $parameters = $data;
        $parameters['id'] = $id;
        $statement->execute($parameters);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM rooms WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

}
