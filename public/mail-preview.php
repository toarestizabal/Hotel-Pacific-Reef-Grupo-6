<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Database\Connection;
use App\Repositories\ReservationRepository;
use App\Services\ConfirmationService;

require __DIR__ . '/_bootstrap.php';

$user = Auth::user();
if ($user === null) {
    header('Location: /login.php?return=' . rawurlencode((string) ($_SERVER['REQUEST_URI'] ?? '/index.php')));
    exit;
}
$code = strtoupper(trim((string) ($_GET['code'] ?? '')));
$repository = new ReservationRepository(Connection::create());
$reservation = $repository->findForUserByCode($code, (int) $user['id'], $user['role'] === 'administrator');
if ($reservation === null) {
    http_response_code(404);
    echo 'No se encontró el correo solicitado.';
    exit;
}
$html = (new ConfirmationService())->read($code);
if ($html === null) {
    http_response_code(404);
    echo 'El correo de prueba todavía no ha sido generado.';
    exit;
}
header('Content-Type: text/html; charset=UTF-8');
echo $html;
