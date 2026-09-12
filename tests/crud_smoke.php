<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\RoomRepository;

require dirname(__DIR__) . '/src/Database/Connection.php';
require dirname(__DIR__) . '/src/Repositories/RoomRepository.php';

$repository = new RoomRepository(Connection::create());
$roomNumber = 'CRUD-' . date('His');

try {
    $repository->create([
        'room_type_id' => 1,
        'room_number' => $roomNumber,
        'location' => 'Área de pruebas',
        'description' => 'Registro temporal para comprobar el CRUD.',
        'equipment' => 'Wi-Fi, TV',
        'image_url' => '',
        'status' => 'available',
    ]);

    $created = array_values(array_filter(
        $repository->all(),
        static fn (array $room): bool => $room['room_number'] === $roomNumber
    ))[0] ?? null;
    if ($created === null) {
        throw new RuntimeException('No se pudo consultar el registro creado.');
    }
    echo "CREATE: OK\nREAD: OK\n";

    $repository->update((int) $created['id'], [
        'room_type_id' => 1,
        'room_number' => $roomNumber,
        'location' => 'Área de pruebas actualizada',
        'description' => 'Registro temporal actualizado.',
        'equipment' => 'Wi-Fi, TV, Minibar',
        'image_url' => '',
        'status' => 'maintenance',
    ]);
    $updated = $repository->find((int) $created['id']);
    if ($updated === null || $updated['status'] !== 'maintenance') {
        throw new RuntimeException('No se pudo verificar la actualización.');
    }
    echo "UPDATE: OK\n";

    $repository->delete((int) $created['id']);
    if ($repository->find((int) $created['id']) !== null) {
        throw new RuntimeException('No se pudo verificar la eliminación.');
    }
    echo "DELETE: OK\nCRUD COMPLETO: OK\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'CRUD: ERROR - ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
