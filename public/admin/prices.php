<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\RoomRepository;

session_start();
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
require dirname(__DIR__, 2) . '/src/Repositories/RoomRepository.php';

function escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$error = null;
$types = [];
try {
    $repository = new RoomRepository(Connection::create());
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) { throw new RuntimeException('La sesión del formulario expiró.'); }
        $repository->updateRoomTypePrice((int) $_POST['id'], (float) $_POST['price']);
        header('Location: prices.php?updated=1'); exit;
    }
    $types = $repository->roomTypes();
} catch (Throwable $exception) { $error = $exception->getMessage(); }
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Precios | Hotel Pacific Reef</title><link rel="stylesheet" href="../assets/css/admin.css"><link rel="stylesheet" href="../assets/css/responsive.css"></head><body>
<header class="admin-header"><div><span>HPR</span><div><strong>Hotel Pacific Reef</strong><small>Administración</small></div></div><nav aria-label="Navegación administrativa"><a href="index.php">Resumen</a><a href="rooms.php">Habitaciones</a><a class="active" href="prices.php">Precios</a><a href="reservations.php">Reservas</a><a href="../index.php">Sitio público</a></nav></header>
<main><section class="page-heading"><div><p>Catálogo</p><h1>Precios por categoría</h1><span>El precio actualizado se refleja automáticamente en el sitio público.</span></div></section>
<?php if (isset($_GET['updated'])): ?><div class="notice success">Precio actualizado correctamente.</div><?php endif; ?><?php if ($error): ?><div class="notice error"><?= escape($error) ?></div><?php endif; ?>
<section class="price-grid"><?php foreach ($types as $type): ?><article class="panel price-card"><div class="panel-heading"><span>Categoría</span><h2><?= escape($type['name']) ?></h2></div><p><?= escape($type['description']) ?></p><dl><div><dt>Capacidad máxima</dt><dd><?= (int) $type['max_guests'] ?> huéspedes</dd></div><div><dt>Precio actual</dt><dd>$<?= number_format((float) $type['base_price'], 0, ',', '.') ?></dd></div></dl><form method="post"><input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>"><input type="hidden" name="id" value="<?= (int) $type['id'] ?>"><label>Nuevo precio diario<input type="number" name="price" min="1" step="1000" value="<?= (int) $type['base_price'] ?>" required></label><button type="submit">Guardar precio</button></form></article><?php endforeach; ?></section>
</main></body></html>
