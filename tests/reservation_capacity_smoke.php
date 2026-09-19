<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\ReservationRepository;

require dirname(__DIR__) . '/src/Database/Connection.php';
require dirname(__DIR__) . '/src/Repositories/ReservationRepository.php';

$pdo = Connection::create();
$repository = new ReservationRepository($pdo);
$roomId = (int) $pdo->query("SELECT id FROM rooms WHERE room_number = 'P-301'")->fetchColumn();
$room = $repository->room($roomId);

if ($room === null || (int) $room['capacity'] !== 2) {
    throw new RuntimeException('La capacidad de P-301 debe ser de dos personas.');
}

$before = (int) $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
$checkIn = new DateTimeImmutable('+30 days');
$checkOut = $checkIn->modify('+2 days');

try {
    $repository->createConfirmed([
        'room_id' => $roomId,
        'check_in' => $checkIn->format('Y-m-d'),
        'check_out' => $checkOut->format('Y-m-d'),
        'guests' => 3,
        'full_name' => 'Prueba de capacidad',
        'email' => 'capacidad@example.test',
    ]);
    throw new RuntimeException('Se aceptó una reserva que excede la capacidad.');
} catch (RuntimeException $exception) {
    if (!str_contains($exception->getMessage(), 'cantidad de huéspedes')) {
        throw $exception;
    }
}

$after = (int) $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
if ($after !== $before) {
    throw new RuntimeException('La prueba dejó una reserva en la base.');
}

echo "CAPACIDAD POR HABITACIÓN: OK\nRESERVA EXCEDIDA: RECHAZADA\nSIN DATOS DE PRUEBA PERMANENTES: OK\n";
