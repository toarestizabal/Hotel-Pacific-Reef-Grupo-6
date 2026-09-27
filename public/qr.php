<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\ReservationRepository;
use App\Services\QrCodeService;

require __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/src/Database/Connection.php';
require_once dirname(__DIR__) . '/src/Repositories/ReservationRepository.php';
require_once dirname(__DIR__) . '/src/Services/QrCodeService.php';

$token = strtolower(trim((string) ($_GET['token'] ?? '')));
$reservation = (new ReservationRepository(Connection::create()))->findByVerificationToken($token);

if ($reservation === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Código QR no válido.';
    exit;
}

$verificationUrl = applicationUrl() . '/ticket.php?token=' . rawurlencode($token);
$png = (new QrCodeService())->png($verificationUrl);

header('Content-Type: image/png');
header('Content-Length: ' . strlen($png));
header('Content-Disposition: inline; filename="reserva-' . $reservation['reservation_code'] . '.png"');
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');
echo $png;
