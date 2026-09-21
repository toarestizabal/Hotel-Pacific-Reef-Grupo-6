<?php

declare(strict_types=1);

use App\Auth\Auth;

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::validCsrf($_POST['csrf_token'] ?? null)) {
    Auth::logout();
}

header('Location: /index.php');
exit;
