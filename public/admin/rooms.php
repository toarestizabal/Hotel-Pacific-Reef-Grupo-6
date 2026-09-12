<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\RoomRepository;

session_start();

require dirname(__DIR__, 2) . '/src/Database/Connection.php';
require dirname(__DIR__, 2) . '/src/Repositories/RoomRepository.php';

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function equipmentText(string $json): string
{
    $items = json_decode($json, true);
    return is_array($items) ? implode(', ', $items) : '';
}

$messages = [
    'created' => 'Habitación creada correctamente.',
    'updated' => 'Habitación actualizada correctamente.',
    'deleted' => 'Habitación eliminada correctamente.',
];
$statusOptions = [
    'available' => 'Disponible',
    'occupied' => 'Ocupada',
    'maintenance' => 'Mantención',
    'inactive' => 'Inactiva',
];
$error = null;
$repository = null;
$rooms = [];
$roomTypes = [];
$editingRoom = null;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $repository = new RoomRepository(Connection::create());

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('La sesión del formulario expiró. Inténtalo nuevamente.');
        }

        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'delete') {
            $repository->delete((int) $_POST['id']);
            header('Location: rooms.php?result=deleted');
            exit;
        }

        foreach (['room_type_id', 'room_number', 'location', 'description', 'equipment', 'status'] as $field) {
            if (trim((string) ($_POST[$field] ?? '')) === '') {
                throw new InvalidArgumentException('Completa todos los campos obligatorios.');
            }
        }
        if (!array_key_exists((string) $_POST['status'], $statusOptions)) {
            throw new InvalidArgumentException('El estado seleccionado no es válido.');
        }

        if ($action === 'create') {
            $repository->create($_POST);
            header('Location: rooms.php?result=created');
            exit;
        }
        if ($action === 'update') {
            $repository->update((int) $_POST['id'], $_POST);
            header('Location: rooms.php?result=updated');
            exit;
        }
    }

    $roomTypes = $repository->roomTypes();
    $rooms = $repository->all();
    if (isset($_GET['edit'])) {
        $editingRoom = $repository->find((int) $_GET['edit']);
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

$form = $editingRoom ?? [
    'id' => 0,
    'room_type_id' => $roomTypes[0]['id'] ?? 1,
    'room_number' => '',
    'location' => '',
    'description' => '',
    'equipment' => 'Wi-Fi, TV, Baño privado',
    'image_url' => '',
    'status' => 'available',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de habitaciones | Hotel Pacific Reef</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<header class="admin-header">
    <div><span>HPR</span><div><strong>Hotel Pacific Reef</strong><small>Administración de habitaciones</small></div></div>
    <a href="../index.php">Volver al sitio</a>
</header>
<main>
    <section class="page-heading">
        <div><p>Gestión interna</p><h1>CRUD de habitaciones</h1></div>
        <span class="database-badge">MariaDB · Conectado</span>
    </section>

    <?php if (isset($_GET['result'], $messages[$_GET['result']])): ?>
        <div class="notice success"><?= escape($messages[$_GET['result']]) ?></div>
    <?php endif; ?>
    <?php if ($error !== null): ?>
        <div class="notice error"><strong>No fue posible completar la operación.</strong><br><?= escape($error) ?></div>
    <?php endif; ?>

    <div class="admin-layout">
        <section class="panel form-panel">
            <div class="panel-heading"><span><?= $editingRoom ? 'Editar' : 'Nueva' ?></span><h2><?= $editingRoom ? 'Actualizar habitación' : 'Registrar habitación' ?></h2></div>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="<?= $editingRoom ? 'update' : 'create' ?>">
                <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
                <label>Tipo de habitación<select name="room_type_id" required><?php foreach ($roomTypes as $type): ?><option value="<?= (int) $type['id'] ?>" <?= (int) $form['room_type_id'] === (int) $type['id'] ? 'selected' : '' ?>><?= escape($type['name']) ?></option><?php endforeach; ?></select></label>
                <label>Número<input name="room_number" value="<?= escape((string) $form['room_number']) ?>" maxlength="20" required></label>
                <label>Ubicación<input name="location" value="<?= escape((string) $form['location']) ?>" maxlength="120" required></label>
                <label>Descripción<textarea name="description" maxlength="500" required><?= escape((string) $form['description']) ?></textarea></label>
                <label>Equipamiento <small>Separado por comas</small><input name="equipment" value="<?= escape(is_string($form['equipment']) && str_starts_with($form['equipment'], '[') ? equipmentText($form['equipment']) : (string) $form['equipment']) ?>" required></label>
                <label>Imagen URL <small>Opcional</small><input name="image_url" type="url" value="<?= escape((string) ($form['image_url'] ?? '')) ?>"></label>
                <label>Estado<select name="status" required><?php foreach ($statusOptions as $value => $label): ?><option value="<?= $value ?>" <?= $form['status'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
                <div class="form-actions"><button type="submit"><?= $editingRoom ? 'Guardar cambios' : 'Crear habitación' ?></button><?php if ($editingRoom): ?><a href="rooms.php">Cancelar</a><?php endif; ?></div>
            </form>
        </section>

        <section class="panel table-panel">
            <div class="panel-heading"><span>Registros</span><h2>Habitaciones</h2><small><?= count($rooms) ?> registros</small></div>
            <div class="table-wrap"><table><thead><tr><th>Número</th><th>Tipo</th><th>Ubicación</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            <?php foreach ($rooms as $room): ?><tr><td><strong><?= escape($room['room_number']) ?></strong><small><?= escape(equipmentText($room['equipment'])) ?></small></td><td><?= escape($room['room_type_name']) ?></td><td><?= escape($room['location']) ?></td><td><span class="status status-<?= escape($room['status']) ?>"><?= escape($statusOptions[$room['status']] ?? $room['status']) ?></span></td><td><div class="row-actions"><a href="?edit=<?= (int) $room['id'] ?>">Editar</a><form method="post" onsubmit="return confirm('¿Eliminar esta habitación?');"><input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $room['id'] ?>"><button class="delete" type="submit">Eliminar</button></form></div></td></tr><?php endforeach; ?>
            <?php if ($rooms === []): ?><tr><td colspan="5" class="empty">No existen habitaciones registradas.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>
    </div>
</main>
</body>
</html>

