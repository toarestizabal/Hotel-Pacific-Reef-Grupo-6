<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\ReservationRepository;

session_start();
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
require dirname(__DIR__, 2) . '/src/Repositories/ReservationRepository.php';

function escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$labels = ['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'cancelled' => 'Cancelada', 'completed' => 'Completada'];
$search = trim((string) ($_GET['q'] ?? ''));
$error = null; $reservations = [];
try {
    $repository = new ReservationRepository(Connection::create());
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) { throw new RuntimeException('La sesión del formulario expiró.'); }
        $repository->updateStatus((int) $_POST['id'], (string) $_POST['status']);
        header('Location: reservations.php?updated=1'); exit;
    }
    $reservations = $repository->all($search);
} catch (Throwable $exception) { $error = $exception->getMessage(); }
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reservas | Hotel Pacific Reef</title><link rel="stylesheet" href="../assets/css/admin.css"><link rel="stylesheet" href="../assets/css/responsive.css"></head><body>
<header class="admin-header"><div><span>HPR</span><div><strong>Hotel Pacific Reef</strong><small>Administración</small></div></div><nav aria-label="Navegación administrativa"><a href="index.php">Resumen</a><a href="rooms.php">Habitaciones</a><a href="prices.php">Precios</a><a class="active" href="reservations.php">Reservas</a><a href="../index.php">Sitio público</a></nav></header>
<main><section class="page-heading"><div><p>Operación</p><h1>Gestión de reservas</h1><span>Consulta y actualiza las reservas registradas.</span></div></section>
<?php if (isset($_GET['updated'])): ?><div class="notice success">Estado de la reserva actualizado.</div><?php endif; ?><?php if ($error): ?><div class="notice error"><?= escape($error) ?></div><?php endif; ?>
<section class="panel table-panel"><div class="panel-toolbar"><div class="panel-heading"><span>Registros</span><h2>Reservas</h2></div><form class="search-form" method="get"><label for="q">Buscar</label><input id="q" name="q" value="<?= escape($search) ?>" placeholder="Código, cliente, correo o habitación"><button>Buscar</button></form></div><div class="table-wrap"><table><thead><tr><th>Código</th><th>Cliente</th><th>Habitación</th><th>Estadía</th><th>Pago</th><th>Estado</th></tr></thead><tbody>
<?php foreach ($reservations as $reservation): ?><tr><td><strong><?= escape($reservation['reservation_code']) ?></strong></td><td><?= escape($reservation['full_name']) ?><small><?= escape($reservation['email']) ?></small></td><td><?= escape($reservation['room_number']) ?><small><?= escape($reservation['category']) ?></small></td><td><?= escape($reservation['check_in']) ?><small>hasta <?= escape($reservation['check_out']) ?></small></td><td><strong>$<?= number_format((float) $reservation['deposit_amount'], 0, ',', '.') ?></strong><small>30 % de $<?= number_format((float) $reservation['total_amount'], 0, ',', '.') ?></small></td><td><form class="status-form" method="post"><input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>"><input type="hidden" name="id" value="<?= (int) $reservation['id'] ?>"><select name="status" aria-label="Estado de <?= escape($reservation['reservation_code']) ?>"><?php foreach ($labels as $value => $label): ?><option value="<?= $value ?>" <?= $reservation['status'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select><button>Guardar</button></form></td></tr><?php endforeach; ?>
<?php if ($reservations === []): ?><tr><td colspan="6" class="empty">No se encontraron reservas.</td></tr><?php endif; ?></tbody></table></div></section>
</main></body></html>
