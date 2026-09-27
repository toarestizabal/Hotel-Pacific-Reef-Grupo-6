<?php

declare(strict_types=1);

use App\Services\ConfirmationService;
use App\Services\QrCodeService;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Services/ConfirmationService.php';
require dirname(__DIR__) . '/src/Services/QrCodeService.php';

$directory = dirname(__DIR__) . '/.local/test-mail-' . bin2hex(random_bytes(4));
$service = new ConfirmationService($directory);
$token = str_repeat('a', 64);
$reservation = [
    'code' => 'HPR-TEST-ABC123',
    'verification_token' => $token,
    'room_number' => 'P-305',
    'check_in' => '2026-10-10',
    'check_out' => '2026-10-12',
    'full_name' => 'Cliente de prueba',
    'total' => 285000,
    'deposit' => 85500,
    'services' => [['name' => 'Traslado', 'quantity' => 1]],
];

try {
    $result = $service->deliverToTestOutbox($reservation, 'http://localhost:8000');
    $html = $service->read($reservation['code']);
    if ($html === null || !str_contains($html, 'HPR-TEST-ABC123') || !str_contains($html, 'Traslado')) {
        throw new RuntimeException('El correo de prueba no contiene los datos de la reserva.');
    }
    if ($result['qr_url'] !== 'http://localhost:8000/qr.php?token=' . $token) {
        throw new RuntimeException('La URL del código QR dinámico es incorrecta.');
    }
    if ($result['ticket_url'] !== 'http://localhost:8000/ticket.php?token=' . $token) {
        throw new RuntimeException('El enlace seguro del ticket es incorrecto.');
    }
    $png = (new QrCodeService())->png($result['ticket_url']);
    if (!str_starts_with($png, "\x89PNG\r\n\x1a\n")) {
        throw new RuntimeException('El código QR no fue generado como una imagen PNG válida.');
    }
    echo "TICKET DINÁMICO: OK\nCÓDIGO QR PNG: OK\nCORREO DE PRUEBA: OK\n";
} finally {
    $file = $directory . DIRECTORY_SEPARATOR . $reservation['code'] . '.html';
    if (is_file($file)) {
        unlink($file);
    }
    if (is_dir($directory)) {
        rmdir($directory);
    }
}
