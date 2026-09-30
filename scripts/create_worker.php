<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\UserRepository;

require dirname(__DIR__) . '/src/autoload.php';

[$script, $fullName, $email, $password] = array_pad($argv, 4, null);
if ($fullName === null || $email === null || $password === null) {
    fwrite(STDERR, "Uso: php scripts/create_worker.php \"Nombre\" correo clave\n");
    exit(1);
}

try {
    $user = (new UserRepository(Connection::create()))->upsertWorker($fullName, $email, $password);
    echo 'Trabajador listo: ' . $user['email'] . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
