<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Database\Connection;
use App\Repositories\UserRepository;
use App\Support\I18n;

require __DIR__ . '/_bootstrap.php';

$language = (string) ($_GET['lang'] ?? 'es');
I18n::setLanguage($language);

$user = Auth::user();
if ($user !== null) {
    try {
        (new UserRepository(Connection::create()))->updateLanguage((int) $user['id'], I18n::language());
        $_SESSION['auth_user']['preferred_language'] = I18n::language();
    } catch (Throwable) {
        // El cambio permanece en la sesión aunque la base no esté disponible.
    }
}

$return = localReturnPath((string) ($_GET['return'] ?? '/index.php'));
header('Location: ' . $return);
exit;
