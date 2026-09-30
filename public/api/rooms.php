<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\RoomRepository;
use App\Services\AvailabilityService;

require __DIR__ . '/_bootstrap.php';

try {
    $availability = (new AvailabilityService(new RoomRepository(Connection::create())))->search(
        (string) ($_GET['check_in'] ?? ''),
        (string) ($_GET['check_out'] ?? ''),
        (int) ($_GET['guests'] ?? 0)
    );

    jsonResponse([
        'success' => true,
        'data' => $availability['rooms'],
        'meta' => [
            'check_in' => (string) $_GET['check_in'],
            'check_out' => (string) $_GET['check_out'],
            'guests' => (int) $_GET['guests'],
            'nights' => $availability['nights'],
            'count' => count($availability['rooms']),
        ],
    ]);
} catch (InvalidArgumentException $exception) {
    jsonResponse(['success' => false, 'error' => $exception->getMessage()], 422);
} catch (Throwable) {
    jsonResponse(['success' => false, 'error' => 'No fue posible consultar la disponibilidad.'], 503);
}
