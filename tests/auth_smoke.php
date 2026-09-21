<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\UserRepository;

require dirname(__DIR__) . '/src/Database/Connection.php';
require dirname(__DIR__) . '/src/Repositories/UserRepository.php';

$pdo = Connection::create();
$pdo->beginTransaction();

try {
    $repository = new UserRepository($pdo);
    $email = 'auth-' . bin2hex(random_bytes(5)) . '@example.test';
    $user = $repository->register([
        'full_name' => 'Cliente de prueba',
        'email' => $email,
        'password' => 'PruebaSegura2026!',
        'preferred_language' => 'es',
    ]);

    if ($user['role'] !== 'client' || !(bool) $user['is_active']) {
        throw new RuntimeException('El registro no creó un cliente activo.');
    }
    if ($repository->authenticate($email, 'PruebaSegura2026!') === null) {
        throw new RuntimeException('La autenticación válida fue rechazada.');
    }
    if ($repository->authenticate($email, 'clave-incorrecta') !== null) {
        throw new RuntimeException('La autenticación aceptó una clave incorrecta.');
    }

    $repository->updateAccess((int) $user['id'], 'worker', true, 0);
    $repository->updateLanguage((int) $user['id'], 'en');
    $updated = $repository->find((int) $user['id']);
    if ($updated === null || $updated['role'] !== 'worker' || $updated['preferred_language'] !== 'en') {
        throw new RuntimeException('No fue posible actualizar el rol o idioma.');
    }

    $pdo->rollBack();
    echo "REGISTER OK\nLOGIN OK\nROLE OK\nLANGUAGE OK\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
