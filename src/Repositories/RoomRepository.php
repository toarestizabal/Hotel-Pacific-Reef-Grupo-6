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
                (room_type_id, room_number, location, description, equipment, image_url, status)
             VALUES
                (:room_type_id, :room_number, :location, :description, :equipment, :image_url, :status)'
        );
        $statement->execute($this->parameters($data));
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE rooms SET
                room_type_id = :room_type_id,
                room_number = :room_number,
                location = :location,
                description = :description,
                equipment = :equipment,
                image_url = :image_url,
                status = :status
             WHERE id = :id'
        );
        $parameters = $this->parameters($data);
        $parameters['id'] = $id;
        $statement->execute($parameters);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM rooms WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    private function parameters(array $data): array
    {
        $equipment = array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', (string) $data['equipment'])
        )));

        return [
            'room_type_id' => (int) $data['room_type_id'],
            'room_number' => trim((string) $data['room_number']),
            'location' => trim((string) $data['location']),
            'description' => trim((string) $data['description']),
            'equipment' => json_encode($equipment, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'image_url' => trim((string) ($data['image_url'] ?? '')) ?: null,
            'status' => (string) $data['status'],
        ];
    }
}
