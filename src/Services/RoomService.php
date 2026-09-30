<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RoomRepository;
use InvalidArgumentException;

final class RoomService
{
    private const STATUSES = ['available', 'occupied', 'maintenance', 'inactive'];

    public function __construct(private readonly RoomRepository $rooms)
    {
    }

    public function create(array $input): void
    {
        $this->rooms->create($this->validatedData($input));
    }

    public function update(int $id, array $input): void
    {
        if ($id < 1 || $this->rooms->find($id) === null) {
            throw new InvalidArgumentException('La habitación seleccionada no existe.');
        }

        $this->rooms->update($id, $this->validatedData($input));
    }

    public function delete(int $id): void
    {
        if ($id < 1 || $this->rooms->find($id) === null) {
            throw new InvalidArgumentException('La habitación seleccionada no existe.');
        }

        $this->rooms->delete($id);
    }

    private function validatedData(array $input): array
    {
        $requiredFields = ['room_type_id', 'capacity', 'room_number', 'location', 'description', 'equipment', 'status'];
        foreach ($requiredFields as $field) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                throw new InvalidArgumentException('Completa todos los campos obligatorios.');
            }
        }

        $roomTypeId = filter_var($input['room_type_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $capacity = filter_var($input['capacity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($roomTypeId === false || $capacity === false) {
            throw new InvalidArgumentException('Selecciona un tipo y una capacidad válidos.');
        }

        $roomType = $this->rooms->findRoomType($roomTypeId);
        if ($roomType === null || $capacity > (int) $roomType['max_guests']) {
            throw new InvalidArgumentException('La capacidad supera el máximo de la categoría.');
        }

        $status = (string) $input['status'];
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('El estado seleccionado no es válido.');
        }

        $equipment = array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', (string) $input['equipment'])
        )));

        return [
            'room_type_id' => $roomTypeId,
            'capacity' => $capacity,
            'room_number' => trim((string) $input['room_number']),
            'location' => trim((string) $input['location']),
            'description' => trim((string) $input['description']),
            'equipment' => json_encode($equipment, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'image_url' => trim((string) ($input['image_url'] ?? '')) ?: null,
            'status' => $status,
        ];
    }
}
