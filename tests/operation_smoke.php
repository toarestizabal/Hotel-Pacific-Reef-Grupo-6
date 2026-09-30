<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\OperationRepository;

require dirname(__DIR__) . '/src/autoload.php';

$pdo = Connection::create();
$pdo->beginTransaction();
try {
    $userId = (int) $pdo->query("SELECT id FROM users WHERE role = 'client' LIMIT 1")->fetchColumn();
    if ($userId === 0) {
        $email = 'operation-' . bin2hex(random_bytes(3)) . '@example.test';
        $insertUser = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES ('Prueba Operación', ?, ?, 'client')");
        $insertUser->execute([$email, password_hash('Prueba.2026', PASSWORD_DEFAULT)]);
        $userId = (int) $pdo->lastInsertId();
    }
    $roomId = (int) $pdo->query('SELECT id FROM rooms ORDER BY id LIMIT 1')->fetchColumn();
    $serviceId = (int) $pdo->query('SELECT id FROM services ORDER BY id LIMIT 1')->fetchColumn();
    $code = 'HPR-OP-' . strtoupper(bin2hex(random_bytes(3)));
    $from = date('Y-m-d', strtotime('+10 days'));
    $to = date('Y-m-d', strtotime('+12 days'));
    $insert = $pdo->prepare("INSERT INTO reservations (reservation_code, verification_token, user_id, room_id, check_in, check_out, guests, daily_rate, total_amount, deposit_amount, status) VALUES (?, ?, ?, ?, ?, ?, 2, 68000, 160000, 48000, 'confirmed')");
    $insert->execute([$code, bin2hex(random_bytes(32)), $userId, $roomId, $from, $to]);
    $reservationId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO reservation_services (reservation_id, service_id, quantity, unit_price) VALUES (?, ?, 4, 12000)')->execute([$reservationId, $serviceId]);

    $repository = new OperationRepository($pdo);
    $rows = $repository->schedule(date('Y-m-d', strtotime('+9 days')), date('Y-m-d', strtotime('+13 days')));
    $row = array_values(array_filter($rows, static fn (array $item): bool => $item['reservation_code'] === $code))[0] ?? null;
    if ($row === null || $row['services'] === 'Sin servicios adicionales') {
        throw new RuntimeException('El panel operativo no recuperó la reserva y sus servicios.');
    }
    $summary = $repository->summary([$row]);
    if ($summary !== ['reservations' => 1, 'guests' => 2, 'with_services' => 1]) {
        throw new RuntimeException('Los indicadores operativos son incorrectos.');
    }
    echo "CALENDARIO OPERATIVO: OK\nSERVICIOS CONTRATADOS: OK\nINDICADORES: OK\n";
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
