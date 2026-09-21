<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\UserRepository;

require dirname(__DIR__) . '/src/Database/Connection.php';
require dirname(__DIR__) . '/src/Repositories/UserRepository.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

[$script, $fullName, $email, $password] = array_pad($argv, 4, null);
if ($fullName === null || $email === null || $password === null) {
    fwrite(STDERR, "Uso: php scripts/create_admin.php \"Nombre\" correo clave\n");
    exit(1);
}

$user = (new UserRepository(Connection::create()))->upsertAdministrator($fullName, $email, $password);
fwrite(STDOUT, "Administrador listo: {$user['email']}\n");
