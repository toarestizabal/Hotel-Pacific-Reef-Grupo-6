<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\ReservationRepository;
use App\Services\ReservationService;

require dirname(__DIR__) . '/src/autoload.php';

$pdo = Connection::create();
$repository = new ReservationRepository($pdo);
$service = new ReservationService($repository);
$roomId = (int) $pdo->query("SELECT id FROM rooms WHERE room_number = 'P-301'")->fetchColumn();
$room = $repository->room($roomId);

if ($room === null || (int) $room['capacity'] !== 2) {
    throw new RuntimeException('La capacidad de P-301 debe ser de dos personas.');
}

$before = (int) $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
$checkIn = new DateTimeImmutable('+30 days');
$checkOut = $checkIn->modify('+2 days');

try {
    $service->confirm([
        'room_id' => $roomId,
        'check_in' => $checkIn->format('Y-m-d'),
        'check_out' => $checkOut->format('Y-m-d'),
        'guests' => 3,
    ], 0);
    throw new RuntimeException('Se aceptó una reserva que excede la capacidad.');
} catch (RuntimeException $exception) {
    if (!str_contains($exception->getMessage(), 'cantidad de huéspedes')) {
        throw $exception;
    }
}

try {
    $service->confirm([
        'room_id' => $roomId,
        'check_in' => $checkIn->format('Y-m-d'),
        'check_out' => $checkOut->format('Y-m-d'),
        'guests' => 1,
    ], 0);
    throw new RuntimeException('Se aceptó una reserva sin una cuenta autenticada.');
} catch (RuntimeException $exception) {
    if (!str_contains($exception->getMessage(), 'iniciar sesión')) {
        throw $exception;
    }
}

$after = (int) $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
if ($after !== $before) {
    throw new RuntimeException('La prueba dejó una reserva en la base.');
}

echo "CAPACIDAD POR HABITACIÓN: OK\nRESERVA EXCEDIDA: RECHAZADA\nRESERVA SIN USUARIO: RECHAZADA\nSIN DATOS DE PRUEBA PERMANENTES: OK\n";
