<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Database\Connection;
use App\Repositories\OperationRepository;
use App\Support\I18n;

require dirname(__DIR__) . '/_bootstrap.php';
Auth::requireAnyRole(['worker', 'administrator']);

$from = (string) ($_GET['from'] ?? date('Y-m-d'));
$to = (string) ($_GET['to'] ?? date('Y-m-d', strtotime('+30 days')));
$error = null;
$reservations = [];
$summary = ['reservations' => 0, 'guests' => 0, 'with_services' => 0];

try {
    $repository = new OperationRepository(Connection::create());
    $reservations = $repository->schedule($from, $to);
    $summary = $repository->summary($reservations);
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

$statusLabels = ['pending' => 'Pendiente', 'confirmed' => 'Confirmada'];
?>
<!DOCTYPE html>
<html lang="<?= I18n::language() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del trabajador | Hotel Pacific Reef</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/week7.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body class="worker-page">
<header class="worker-header">
    <div><strong>Hotel Pacific Reef</strong><br><small>Panel del trabajador</small></div>
    <nav class="worker-nav" aria-label="Navegación del trabajador">
        <a href="../index.php">Sitio público</a>
        <a href="<?= escape(languageUrl(I18n::language() === 'es' ? 'en' : 'es')) ?>">ES / EN</a>
        <form action="../logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= escape(Auth::csrfToken()) ?>"><button type="submit">Cerrar sesión</button></form>
    </nav>
</header>
<main class="worker-main">
    <section class="page-heading"><div><p>Operación hotelera</p><h1>Calendario de reservas y servicios</h1><span>Consulta las llegadas, huéspedes y servicios contratados para preparar la atención.</span></div></section>
    <?php if ($error !== null): ?><div class="notice error"><?= escape($error) ?></div><?php endif; ?>

    <form class="filter-panel" method="get">
        <label>Desde<input type="date" name="from" value="<?= escape($from) ?>" required></label>
        <label>Hasta<input type="date" name="to" value="<?= escape($to) ?>" required></label>
        <button type="submit">Consultar calendario</button>
    </form>

    <section class="stats-grid" aria-label="Indicadores del período">
        <article><span>Reservas activas</span><strong><?= (int) $summary['reservations'] ?></strong><small>En el rango seleccionado</small></article>
        <article><span>Huéspedes</span><strong><?= (int) $summary['guests'] ?></strong><small>Total esperado</small></article>
        <article><span>Con servicios</span><strong><?= (int) $summary['with_services'] ?></strong><small>Requieren preparación adicional</small></article>
    </section>

    <section class="operation-grid" aria-label="Reservas del calendario">
        <?php foreach ($reservations as $reservation): ?>
            <article class="operation-card">
                <div><span class="calendar-date"><?= escape((string) $reservation['check_in']) ?></span><p>Salida <?= escape((string) $reservation['check_out']) ?></p></div>
                <div><h3><?= escape((string) $reservation['reservation_code']) ?></h3><p><?= escape((string) $reservation['full_name']) ?> · <?= (int) $reservation['guests'] ?> huéspedes</p><p><?= escape((string) $reservation['email']) ?></p></div>
                <div><h3>Habitación <?= escape((string) $reservation['room_number']) ?></h3><p><?= escape((string) $reservation['category']) ?></p><p><strong>Servicios:</strong> <?= escape((string) $reservation['services']) ?></p><span class="status status-<?= escape((string) $reservation['status']) ?>"><?= escape($statusLabels[$reservation['status']] ?? (string) $reservation['status']) ?></span></div>
            </article>
        <?php endforeach; ?>
        <?php if ($reservations === [] && $error === null): ?><div class="panel empty">No existen reservas activas en el período seleccionado.</div><?php endif; ?>
    </section>
</main>
</body>
</html>
