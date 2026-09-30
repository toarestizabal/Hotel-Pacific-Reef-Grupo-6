<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Database\Connection;
use App\Repositories\ReservationRepository;
use App\Services\ConfirmationService;
use App\Services\ReservationService;
use App\Support\I18n;

$projectRoot = dirname(__DIR__);
require __DIR__ . '/_bootstrap.php';
$roomGalleries = require $projectRoot . '/src/Data/room_galleries.php';

$error = null;
$confirmation = null;
$repository = null;
$services = [];
$authUser = Auth::user();

if ($authUser === null) {
    $return = $_SERVER['REQUEST_METHOD'] === 'GET'
        ? (string) ($_SERVER['REQUEST_URI'] ?? '/index.php#reserva')
        : '/index.php#reserva';
    header('Location: /login.php?return=' . rawurlencode($return));
    exit;
}

$roomId = (int) ($_POST['room_id'] ?? $_GET['room'] ?? 0);
$checkIn = (string) ($_POST['check_in'] ?? $_GET['check_in'] ?? '');
$checkOut = (string) ($_POST['check_out'] ?? $_GET['check_out'] ?? '');
$guests = (int) ($_POST['guests'] ?? $_GET['guests'] ?? 1);
$room = null;

try {
    $repository = new ReservationRepository(Connection::create());
    $services = $repository->services();
    $room = $repository->room($roomId);
    if ($room === null) {
        throw new RuntimeException('Selecciona una habitación válida desde el catálogo.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::validCsrf($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('La sesión del formulario expiró. Vuelve a intentarlo.');
        }
        if (!isset($_POST['confirm_terms'])) {
            throw new RuntimeException('Debes confirmar los datos y fechas de la reserva.');
        }
        $confirmation = (new ReservationService($repository))->confirm($_POST, (int) $authUser['id']);
        $confirmation = array_merge(
            $confirmation,
            (new ConfirmationService())->deliverToTestOutbox($confirmation, applicationUrl())
        );
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

$gallery = $room !== null ? ($roomGalleries[$room['category']] ?? $roomGalleries['Turista']) : [];
$nights = 0;
if ($checkIn !== '' && $checkOut !== '') {
    try {
        $nights = max(0, (int) (new DateTimeImmutable($checkIn))->diff(new DateTimeImmutable($checkOut))->format('%r%a'));
    } catch (Throwable) {
        $nights = 0;
    }
}
$total = $room !== null ? (float) $room['price'] * $nights : 0;
$deposit = round($total * 0.30, 2);
$referenceRates = referenceExchangeRates();
$usdRate = $referenceRates['USD'];
$eurRate = $referenceRates['EUR'];
?>
<!DOCTYPE html>
<html lang="<?= I18n::language() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Proceso de reserva de Hotel Pacific Reef">
    <title><?= $confirmation ? 'Reserva confirmada' : 'Completar reserva' ?> | Hotel Pacific Reef</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/reservation.css">
    <link rel="stylesheet" href="assets/css/week7.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <style>.reservation-page .ticket{display:block;max-width:680px;margin-left:auto;margin-right:auto}</style>
</head>
<body class="reservation-page">
<header class="site-header compact-header">
    <a class="brand" href="index.php" aria-label="Hotel Pacific Reef, volver al inicio">
        <span class="brand-mark" aria-hidden="true">HPR</span>
        <span class="brand-copy"><strong>Hotel Pacific Reef</strong><small>Sistema de reservas</small></span>
    </a>
    <div class="account-links"><a class="back-link" href="index.php#habitaciones">← Volver al catálogo</a><a class="language-button" href="<?= escape(languageUrl(I18n::language() === 'es' ? 'en' : 'es')) ?>" aria-label="Cambiar idioma">ES <span>/</span> EN</a></div>
</header>

<main class="reservation-main">
    <?php if ($confirmation !== null): ?>
        <section class="confirmation-card" aria-labelledby="confirmationTitle">
            <div class="confirmation-icon" aria-hidden="true">✓</div>
            <p class="section-kicker">Pago de prueba aprobado</p>
            <h1 id="confirmationTitle">Reserva confirmada</h1>
            <p>La reserva, el ticket y el correo de confirmación de prueba fueron generados correctamente.</p>

            <div class="ticket ticket-result">
                <div>
                    <span>Código de reserva</span>
                    <strong><?= escape($confirmation['code']) ?></strong>
                    <dl>
                        <div><dt>Habitación</dt><dd><?= escape($confirmation['room_number'] . ' · ' . $confirmation['category']) ?></dd></div>
                        <div><dt>Estadía</dt><dd><?= escape($confirmation['check_in']) ?> al <?= escape($confirmation['check_out']) ?></dd></div>
                        <div><dt>Huéspedes</dt><dd><?= (int) $confirmation['guests'] ?></dd></div>
                        <div><dt>Habitación</dt><dd><?= money((float) $confirmation['room_total']) ?></dd></div>
                        <div><dt>Servicios</dt><dd><?= money((float) $confirmation['service_total']) ?></dd></div>
                        <div><dt>Total</dt><dd><?= money((float) $confirmation['total']) ?></dd></div>
                        <?php if ($usdRate > 0 && $eurRate > 0): ?>
                            <div><dt>Valor referencial</dt><dd><?= foreignMoney((float) $confirmation['total'] * $usdRate, 'USD') ?><br><?= foreignMoney((float) $confirmation['total'] * $eurRate, 'EUR') ?></dd></div>
                        <?php endif; ?>
                        <div><dt>Abono pagado</dt><dd><?= money((float) $confirmation['deposit']) ?></dd></div>
                    </dl>
                    <?php if ($confirmation['services'] !== []): ?>
                        <p><strong>Servicios contratados:</strong></p>
                        <ul>
                            <?php foreach ($confirmation['services'] as $service): ?>
                                <li><?= escape((string) $service['name']) ?> × <?= (int) $service['quantity'] ?> — <?= money((float) $service['subtotal']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <img src="<?= escape($confirmation['qr_url']) ?>" width="260" height="260" alt="Código QR del ticket <?= escape($confirmation['code']) ?>">
            </div>
            <p class="mail-status">El pago fue procesado y el correo quedó disponible en la bandeja de prueba del sistema.</p>
            <div class="confirmation-actions">
                <a class="secondary-button" href="<?= escape($confirmation['ticket_url']) ?>" target="_blank" rel="noopener">Abrir ticket verificable</a>
                <a class="secondary-button" href="<?= escape($confirmation['mail_preview_url']) ?>" target="_blank" rel="noopener">Ver correo generado</a>
                <a class="primary-button" href="index.php">Volver al inicio</a>
            </div>
        </section>
    <?php elseif ($room !== null): ?>
        <section class="reservation-intro">
            <p class="section-kicker">Proceso de reserva</p>
            <h1>Revisa y confirma tu estadía</h1>
            <ol class="stepper" aria-label="Progreso de la reserva">
                <li class="complete"><span>1</span>Selección</li>
                <li class="active"><span>2</span>Datos y pago</li>
                <li><span>3</span>Confirmación</li>
            </ol>
        </section>

        <?php if ($error !== null): ?><div class="reservation-alert" role="alert"><?= escape($error) ?></div><?php endif; ?>

        <div class="reservation-layout">
            <section class="reservation-form-card" aria-labelledby="formTitle">
                <h2 id="formTitle">Datos de la reserva</h2>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= escape(Auth::csrfToken()) ?>">
                    <input type="hidden" name="room_id" value="<?= (int) $room['id'] ?>">
                    <div class="field-grid">
                        <label>Nombre completo<input value="<?= escape((string) $authUser['full_name']) ?>" autocomplete="name" readonly></label>
                        <label>Correo electrónico<input type="email" value="<?= escape((string) $authUser['email']) ?>" autocomplete="email" readonly></label>
                        <label>Fecha de llegada<input name="check_in" type="date" min="<?= date('Y-m-d') ?>" value="<?= escape($checkIn) ?>" required></label>
                        <label>Fecha de salida<input name="check_out" type="date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= escape($checkOut) ?>" required></label>
                        <label>Huéspedes<input name="guests" type="number" min="1" max="<?= (int) $room['capacity'] ?>" value="<?= $guests ?>" required></label>
                        <label>Método de pago<select name="payment_method"><option>Tarjeta de prueba</option></select></label>
                    </div>
                    <?php if ($services !== []): ?>
                        <fieldset class="service-options">
                            <legend>Traslado opcional</legend>
                            <p class="included-services">Desayuno y estacionamiento incluidos sin costo adicional.</p>
                            <?php foreach ($services as $service): ?>
                                <label class="service-option">
                                    <input type="checkbox" name="services[]" value="<?= (int) $service['id'] ?>" data-price="<?= escape((string) $service['price']) ?>" data-name="<?= escape((string) $service['name']) ?>">
                                    <span><strong><?= escape((string) $service['name']) ?></strong><small><?= escape((string) $service['description']) ?></small></span>
                                    <span class="service-price"><?= money((float) $service['price']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endif; ?>
                    <div class="test-payment"><strong>Ambiente de prueba</strong><span>No se solicitarán ni almacenarán datos bancarios reales.</span></div>
                    <label class="terms"><input type="checkbox" name="confirm_terms" value="1" required> Confirmo que los datos y fechas de la reserva son correctos.</label>
                    <button class="primary-button confirm-button" type="submit">Pagar abono y confirmar</button>
                </form>
            </section>

            <aside class="reservation-summary" aria-labelledby="summaryTitle" data-daily-rate="<?= escape((string) $room['price']) ?>" data-usd-rate="<?= escape((string) $usdRate) ?>" data-eur-rate="<?= escape((string) $eurRate) ?>" data-guests="<?= $guests ?>" data-nights="<?= $nights ?>">
                <?php if ($gallery !== []): ?><img src="<?= escape($gallery[0]['src']) ?>" alt="<?= escape($gallery[0]['alt']) ?>"><?php endif; ?>
                <div>
                    <span class="room-code"><?= escape($room['category']) ?></span>
                    <h2 id="summaryTitle">Habitación <?= escape($room['room_number']) ?></h2>
                    <p><?= escape($room['location']) ?></p>
                    <dl>
                        <div><dt>Fechas</dt><dd><?= escape($checkIn ?: 'Por definir') ?> — <?= escape($checkOut ?: 'Por definir') ?></dd></div>
                        <div><dt>Noches</dt><dd id="nightsCount"><?= $nights ?></dd></div>
                        <div><dt>Tarifa diaria</dt><dd><?= money((float) $room['price']) ?></dd></div>
                        <div><dt>Total estadía</dt><dd id="roomTotal"><?= money($total) ?></dd></div>
                        <div><dt>Servicios adicionales</dt><dd id="serviceTotal"><?= money(0) ?></dd></div>
                        <div><dt>Total general</dt><dd id="grandTotal"><?= money($total) ?></dd></div>
                        <?php if ($usdRate > 0 && $eurRate > 0): ?>
                            <div><dt>Valor referencial</dt><dd id="foreignTotal"><?= foreignMoney($total * $usdRate, 'USD') ?><br><?= foreignMoney($total * $eurRate, 'EUR') ?></dd></div>
                        <?php endif; ?>
                        <div class="deposit"><dt>Abono requerido (30 %)</dt><dd id="depositTotal"><?= money($deposit) ?></dd></div>
                    </dl>
                    <?php if ($usdRate > 0 && $eurRate > 0): ?>
                        <a class="exchange-reference" href="https://www.exchangerate-api.com" target="_blank" rel="noopener">Valores aproximados · Rates by Exchange Rate API</a>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    <?php else: ?>
        <section class="confirmation-card"><h1>No fue posible abrir la reserva</h1><p><?= escape($error ?? 'Selecciona una habitación desde el catálogo.') ?></p><a class="primary-button" href="index.php#habitaciones">Ver habitaciones</a></section>
    <?php endif; ?>
</main>
<script src="assets/js/reservation.js" defer></script>
</body>
</html>
