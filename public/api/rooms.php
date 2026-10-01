<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\RoomRepository;
use App\Services\AvailabilityService;

require __DIR__ . '/_bootstrap.php';

try {
    $checkIn = $_GET['check_in'] ?? '';
    $checkOut = $_GET['check_out'] ?? '';
    $guests = $_GET['guests'] ?? '';
    if (!is_string($checkIn) || !is_string($checkOut) || !is_string($guests)) {
        throw new InvalidArgumentException('Los parámetros de búsqueda no son válidos.');
    }

    $availability = (new AvailabilityService(new RoomRepository(Connection::create())))->search(
        $checkIn,
        $checkOut,
        $guests
    );

    jsonResponse([
        'success' => true,
        'data' => $availability['rooms'],
        'meta' => [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => $availability['guests'],
            'nights' => $availability['nights'],
            'count' => count($availability['rooms']),
        ],
    ]);
} catch (InvalidArgumentException $exception) {
    jsonResponse(['success' => false, 'error' => $exception->getMessage()], 422);
} catch (Throwable) {
    jsonResponse(['success' => false, 'error' => 'No fue posible consultar la disponibilidad.'], 503);
}
