<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\ReservationRepository;
use App\Repositories\UserRepository;
use App\Services\ReservationService;

require dirname(__DIR__) . '/src/autoload.php';

$pdo = Connection::create();
$userId = 0;
$reservationId = 0;

try {
    $email = 'services-' . bin2hex(random_bytes(4)) . '@example.test';
    $user = (new UserRepository($pdo))->register([
        'full_name' => 'Cliente Servicios',
        'email' => $email,
        'password' => 'Prueba.2026',
        'preferred_language' => 'es',
    ]);
    $userId = (int) $user['id'];

    $repository = new ReservationRepository($pdo);
    $roomId = (int) $pdo->query("SELECT id FROM rooms WHERE status = 'available' ORDER BY id LIMIT 1")->fetchColumn();
    $serviceIds = $pdo->query("SELECT id FROM services WHERE name = 'Traslado' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    $checkIn = date('Y-m-d', strtotime('+400 days'));
    $checkOut = date('Y-m-d', strtotime('+402 days'));
    $confirmation = (new ReservationService($repository))->confirm([
        'room_id' => $roomId,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'guests' => 2,
        'services' => $serviceIds,
    ], $userId);
    $reservationId = (int) $confirmation['id'];

    if (count($confirmation['services']) !== 1 || (float) $confirmation['service_total'] !== 35000.0) {
        throw new RuntimeException('Los servicios no fueron incluidos en el cálculo de la reserva.');
    }
    if ((float) $confirmation['deposit'] !== round((float) $confirmation['total'] * 0.30, 2)) {
        throw new RuntimeException('El abono no corresponde al 30 % del total con servicios.');
    }
    $stored = $repository->findByCode((string) $confirmation['code']);
    if ($stored === null || count($stored['services']) !== 1 || $stored['services'][0]['name'] !== 'Traslado') {
        throw new RuntimeException('Los servicios contratados no quedaron relacionados con la reserva.');
    }
    echo "RESERVA CON SERVICIOS: OK\nTOTAL Y ABONO: OK\nRELACIONES EN BASE DE DATOS: OK\n";
} finally {
    if ($reservationId > 0) {
        $pdo->prepare('DELETE FROM payments WHERE reservation_id = ?')->execute([$reservationId]);
        $pdo->prepare('DELETE FROM reservation_services WHERE reservation_id = ?')->execute([$reservationId]);
        $pdo->prepare('DELETE FROM reservations WHERE id = ?')->execute([$reservationId]);
    }
    if ($userId > 0) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
    }
}
