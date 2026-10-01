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
        if ($equipment === [] || count($equipment) > 20) {
            throw new InvalidArgumentException('Ingresa entre 1 y 20 elementos de equipamiento.');
        }

        $roomNumber = trim((string) $input['room_number']);
        $location = trim((string) $input['location']);
        $description = trim((string) $input['description']);
        if (mb_strlen($roomNumber) > 20 || mb_strlen($location) > 120 || mb_strlen($description) > 500) {
            throw new InvalidArgumentException('Uno de los textos supera el largo permitido.');
        }
        foreach ($equipment as $item) {
            if (mb_strlen($item) > 80) {
                throw new InvalidArgumentException('Cada elemento de equipamiento admite hasta 80 caracteres.');
            }
        }

        $imageUrl = trim((string) ($input['image_url'] ?? ''));
        if ($imageUrl !== '') {
            $scheme = strtolower((string) parse_url($imageUrl, PHP_URL_SCHEME));
            if (mb_strlen($imageUrl) > 500
                || filter_var($imageUrl, FILTER_VALIDATE_URL) === false
                || !in_array($scheme, ['http', 'https'], true)) {
                throw new InvalidArgumentException('La URL de la imagen no es válida.');
            }
        }

        return [
            'room_type_id' => $roomTypeId,
            'capacity' => $capacity,
            'room_number' => $roomNumber,
            'location' => $location,
            'description' => $description,
            'equipment' => json_encode($equipment, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
            'status' => $status,
        ];
    }
}
