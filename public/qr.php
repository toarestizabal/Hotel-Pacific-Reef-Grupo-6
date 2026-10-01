<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\ReservationRepository;
use App\Services\QrCodeService;

require __DIR__ . '/_bootstrap.php';

$token = strtolower(trim((string) ($_GET['token'] ?? '')));
try {
    $reservation = (new ReservationRepository(Connection::create()))->findByVerificationToken($token);
} catch (Throwable) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'No fue posible generar el código QR en este momento.';
    exit;
}

if ($reservation === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Código QR no válido.';
    exit;
}

$verificationUrl = applicationUrl() . '/ticket.php?token=' . rawurlencode($token);
$png = (new QrCodeService())->png($verificationUrl);
$safeCode = preg_replace('/[^A-Z0-9-]/', '', strtoupper((string) $reservation['reservation_code']));

header('Content-Type: image/png');
header('Content-Length: ' . strlen($png));
header('Content-Disposition: inline; filename="reserva-' . $safeCode . '.png"');
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');
echo $png;
