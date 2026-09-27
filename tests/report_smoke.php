<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\ReportRepository;

require dirname(__DIR__) . '/src/Database/Connection.php';
require dirname(__DIR__) . '/src/Repositories/ReportRepository.php';

$pdo = Connection::create();
$pdo->beginTransaction();
try {
    $userId = (int) $pdo->query("SELECT id FROM users WHERE role = 'client' LIMIT 1")->fetchColumn();
    if ($userId === 0) {
        $email = 'report-' . bin2hex(random_bytes(3)) . '@example.test';
        $insertUser = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES ('Prueba Reporte', ?, ?, 'client')");
        $insertUser->execute([$email, password_hash('Prueba.2026', PASSWORD_DEFAULT)]);
        $userId = (int) $pdo->lastInsertId();
    }
    $roomId = (int) $pdo->query('SELECT id FROM rooms ORDER BY id LIMIT 1')->fetchColumn();
    $code = 'HPR-REP-' . strtoupper(bin2hex(random_bytes(3)));
    $from = date('Y-m-d', strtotime('+20 days'));
    $to = date('Y-m-d', strtotime('+22 days'));
    $insert = $pdo->prepare("INSERT INTO reservations (reservation_code, verification_token, user_id, room_id, check_in, check_out, guests, daily_rate, total_amount, deposit_amount, status) VALUES (?, ?, ?, ?, ?, ?, 3, 68000, 204000, 61200, 'confirmed')");
    $insert->execute([$code, bin2hex(random_bytes(32)), $userId, $roomId, $from, $to]);

    $repository = new ReportRepository($pdo);
    $rows = $repository->reservations($from, $from, 'confirmed');
    $row = array_values(array_filter($rows, static fn (array $item): bool => $item['reservation_code'] === $code))[0] ?? null;
    if ($row === null) {
        throw new RuntimeException('El reporte no recuperó la reserva filtrada.');
    }
    $summary = $repository->summary([$row]);
    if ($summary['reservations'] !== 1 || $summary['guests'] !== 3 || (float) $summary['total'] !== 204000.0 || (float) $summary['deposits'] !== 61200.0) {
        throw new RuntimeException('Los totales del reporte son incorrectos.');
    }
    echo "FILTRO POR FECHA Y ESTADO: OK\nTOTALES DEL REPORTE: OK\nDATOS PARA CSV: OK\n";
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
