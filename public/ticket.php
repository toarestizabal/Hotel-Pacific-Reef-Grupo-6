<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\ReservationRepository;
use App\Services\ConfirmationService;
use App\Support\I18n;

require __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__) . '/src/Database/Connection.php';
require_once dirname(__DIR__) . '/src/Repositories/ReservationRepository.php';
require_once dirname(__DIR__) . '/src/Services/ConfirmationService.php';

$token = strtolower(trim((string) ($_GET['token'] ?? '')));
$reservation = null;
$error = null;
try {
    $reservation = (new ReservationRepository(Connection::create()))->findByVerificationToken($token);
    if ($reservation === null) {
        http_response_code(404);
        throw new RuntimeException('No se encontró una reserva válida para este enlace.');
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}
$statusLabels = ['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'cancelled' => 'Cancelada', 'completed' => 'Completada'];
?>
<!DOCTYPE html><html lang="<?= I18n::language() ?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Verificación de reserva | Hotel Pacific Reef</title><link rel="stylesheet" href="assets/css/styles.css"><link rel="stylesheet" href="assets/css/week7.css"><link rel="stylesheet" href="assets/css/responsive.css"></head><body class="ticket-page">
<main class="ticket-verification">
<?php if ($reservation !== null): ?>
    <section class="verification-card"><span class="verification-mark">✓</span><p class="section-kicker">Ticket verificable</p><h1><?= escape($reservation['reservation_code']) ?></h1><span class="status status-<?= escape($reservation['status']) ?>"><?= escape($statusLabels[$reservation['status']] ?? $reservation['status']) ?></span><dl><div><dt>Habitación</dt><dd><?= escape($reservation['room_number'] . ' · ' . $reservation['category']) ?></dd></div><div><dt>Estadía</dt><dd><?= escape($reservation['check_in']) ?> al <?= escape($reservation['check_out']) ?></dd></div><div><dt>Huéspedes</dt><dd><?= (int) $reservation['guests'] ?></dd></div></dl><p>Presenta este código durante el check-in.</p></section>
<?php else: ?>
    <section class="verification-card invalid"><span class="verification-mark">!</span><h1>Ticket no válido</h1><p><?= escape((string) $error) ?></p></section>
<?php endif; ?>
</main></body></html>
