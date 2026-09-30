<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Database\Connection;
use App\Repositories\ReportRepository;
use App\Services\ReservationCsvExporter;
use App\Support\I18n;

require dirname(__DIR__) . '/_bootstrap.php';
Auth::requireRole('administrator');

$from = (string) ($_GET['from'] ?? date('Y-m-01'));
$to = (string) ($_GET['to'] ?? date('Y-m-t'));
$status = (string) ($_GET['status'] ?? 'all');
$error = null;
$rows = [];
$summary = ['reservations' => 0, 'guests' => 0, 'total' => 0, 'deposits' => 0];
$repository = new ReportRepository(Connection::create());

try {
    $rows = $repository->reservations($from, $to, $status);
    $summary = $repository->summary($rows);
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

if ($error === null && ($_GET['format'] ?? '') === 'csv') {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reporte-reservas-' . $from . '-' . $to . '.csv"');
    $output = fopen('php://output', 'wb');
    (new ReservationCsvExporter())->write($output, $rows);
    fclose($output);
    exit;
}

$statusLabels = ['all' => 'Todos', 'pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'cancelled' => 'Cancelada', 'completed' => 'Completada'];
?>
<!DOCTYPE html>
<html lang="<?= I18n::language() ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes | Hotel Pacific Reef</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/week7.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body class="report-page">
<?php renderAdminHeader('reports'); ?>
<main class="report-main">
    <section class="page-heading"><div><p>Información de gestión</p><h1>Reporte de reservas</h1><span>Filtra por período y estado, revisa los totales y descarga los resultados.</span></div></section>
    <?php if ($error !== null): ?><div class="notice error"><?= escape($error) ?></div><?php endif; ?>

    <div class="report-actions">
        <form class="filter-panel" method="get">
            <label>Desde<input type="date" name="from" value="<?= escape($from) ?>" required></label>
            <label>Hasta<input type="date" name="to" value="<?= escape($to) ?>" required></label>
            <label>Estado<select name="status"><?php foreach ($statusLabels as $value => $label): ?><option value="<?= escape($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= escape($label) ?></option><?php endforeach; ?></select></label>
            <button type="submit">Generar reporte</button>
        </form>
        <a class="download-button" href="?<?= escape(http_build_query(['from' => $from, 'to' => $to, 'status' => $status, 'format' => 'csv'])) ?>">Descargar CSV</a>
    </div>

    <section class="stats-grid" aria-label="Resumen del reporte">
        <article><span>Reservas</span><strong><?= (int) $summary['reservations'] ?></strong><small>Registros encontrados</small></article>
        <article><span>Huéspedes</span><strong><?= (int) $summary['guests'] ?></strong><small>Total del período</small></article>
        <article><span>Ventas</span><strong><?= money((float) $summary['total']) ?></strong><small>Valor total reservado</small></article>
        <article><span>Abonos</span><strong><?= money((float) $summary['deposits']) ?></strong><small>Pagos registrados</small></article>
    </section>

    <section class="panel table-panel"><div class="panel-heading"><span>Detalle</span><h2>Reservas del período</h2></div><div class="table-wrap"><table><thead><tr><th>Código</th><th>Cliente</th><th>Habitación</th><th>Estadía</th><th>Huéspedes</th><th>Total</th><th>Estado</th></tr></thead><tbody>
    <?php foreach ($rows as $row): ?><tr><td><strong><?= escape((string) $row['reservation_code']) ?></strong></td><td><?= escape((string) $row['full_name']) ?><small><?= escape((string) $row['email']) ?></small></td><td><?= escape((string) $row['room_number'] . ' · ' . (string) $row['category']) ?></td><td><?= escape((string) $row['check_in']) ?> — <?= escape((string) $row['check_out']) ?></td><td><?= (int) $row['guests'] ?></td><td><?= money((float) $row['total_amount']) ?></td><td><span class="status status-<?= escape((string) $row['status']) ?>"><?= escape($statusLabels[$row['status']] ?? (string) $row['status']) ?></span></td></tr><?php endforeach; ?>
    <?php if ($rows === []): ?><tr><td colspan="7" class="empty">No se encontraron reservas para los filtros seleccionados.</td></tr><?php endif; ?>
    </tbody></table></div></section>
</main>
</body>
</html>
