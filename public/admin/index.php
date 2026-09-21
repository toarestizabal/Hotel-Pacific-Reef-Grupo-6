<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Database\Connection;
use App\Repositories\ReservationRepository;
use App\Support\I18n;

require dirname(__DIR__) . '/_bootstrap.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
require dirname(__DIR__, 2) . '/src/Repositories/ReservationRepository.php';
Auth::requireRole('administrator');

$error = null;
$stats = ['rooms' => 0, 'available_rooms' => 0, 'confirmed_reservations' => 0, 'clients' => 0];
$recent = [];
try {
    $repository = new ReservationRepository(Connection::create());
    $stats = $repository->dashboardStats();
    $recent = $repository->recent();
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

$statusLabels = ['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'cancelled' => 'Cancelada', 'completed' => 'Completada'];
?>
<!DOCTYPE html>
<html lang="<?= I18n::language() ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel administrativo | Hotel Pacific Reef</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<?php renderAdminHeader('index'); ?>
<main>
    <section class="page-heading"><div><p>Gestión interna</p><h1>Resumen operativo</h1><span>Información principal del Hotel Pacific Reef</span></div></section>
    <?php if ($error !== null): ?><div class="notice error"><?= escape($error) ?></div><?php endif; ?>

    <section class="stats-grid" aria-label="Indicadores principales">
        <article><span>Habitaciones registradas</span><strong><?= $stats['rooms'] ?></strong><small>Inventario del prototipo</small></article>
        <article><span>Disponibles</span><strong><?= $stats['available_rooms'] ?></strong><small>Estado actual</small></article>
        <article><span>Reservas confirmadas</span><strong><?= $stats['confirmed_reservations'] ?></strong><small>Próximas estadías</small></article>
        <article><span>Clientes registrados</span><strong><?= $stats['clients'] ?></strong><small>Cuentas de cliente</small></article>
    </section>

    <section class="dashboard-grid">
        <article class="panel quick-panel"><div class="panel-heading"><span>Accesos rápidos</span><h2>Gestión del hotel</h2></div><div class="quick-links"><a href="rooms.php"><strong>Habitaciones</strong><span>Crear, editar, cambiar estados y equipamiento.</span></a><a href="prices.php"><strong>Precios</strong><span>Actualizar el valor diario por categoría.</span></a><a href="reservations.php"><strong>Reservas</strong><span>Buscar, confirmar, completar o cancelar.</span></a></div></article>
        <article class="panel table-panel"><div class="panel-heading"><span>Actividad reciente</span><h2>Últimas reservas</h2></div><div class="table-wrap"><table><thead><tr><th>Reserva</th><th>Cliente</th><th>Habitación</th><th>Estado</th></tr></thead><tbody>
        <?php foreach ($recent as $reservation): ?><tr><td><strong><?= escape($reservation['reservation_code']) ?></strong><small><?= escape($reservation['check_in']) ?> — <?= escape($reservation['check_out']) ?></small></td><td><?= escape($reservation['full_name']) ?></td><td><?= escape($reservation['room_number']) ?></td><td><span class="status status-<?= escape($reservation['status']) ?>"><?= escape($statusLabels[$reservation['status']] ?? $reservation['status']) ?></span></td></tr><?php endforeach; ?>
        <?php if ($recent === []): ?><tr><td colspan="4" class="empty">Todavía no existen reservas registradas.</td></tr><?php endif; ?>
        </tbody></table></div></article>
    </section>
</main>
</body>
</html>
