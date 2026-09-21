<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Database\Connection;
use App\Repositories\UserRepository;
use App\Support\I18n;

require __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__) . '/src/Database/Connection.php';
require_once dirname(__DIR__) . '/src/Repositories/UserRepository.php';

if (Auth::user() !== null) {
    header('Location: /index.php');
    exit;
}

$error = null;
$email = trim((string) ($_POST['email'] ?? ''));
$return = rawurldecode((string) ($_POST['return'] ?? $_GET['return'] ?? ''));
if ($return !== '' && (!str_starts_with($return, '/') || str_starts_with($return, '//'))) {
    $return = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!Auth::validCsrf($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('La sesión del formulario expiró. Vuelve a intentarlo.');
        }
        $user = (new UserRepository(Connection::create()))->authenticate($email, (string) ($_POST['password'] ?? ''));
        if ($user === null) {
            throw new RuntimeException('Credenciales incorrectas o cuenta inactiva.');
        }
        Auth::login($user);
        $destination = $return !== '' ? $return : ($user['role'] === 'administrator' ? '/admin/index.php' : '/index.php');
        header('Location: ' . $destination);
        exit;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= I18n::language() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | Hotel Pacific Reef</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body class="auth-page">
<header class="site-header compact-header">
    <a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true">HPR</span><span class="brand-copy"><strong>Hotel Pacific Reef</strong><small>Sistema de reservas</small></span></a>
    <a class="language-button" href="<?= escape(languageUrl(I18n::language() === 'es' ? 'en' : 'es')) ?>" aria-label="Cambiar idioma">ES <span>/</span> EN</a>
</header>
<main class="auth-main">
    <section class="auth-card" aria-labelledby="loginTitle">
        <p class="section-kicker">Mi cuenta</p>
        <h1 id="loginTitle">Iniciar sesión</h1>
        <p>Ingresa con tu correo y contraseña.</p>
        <?php if ($error !== null): ?><div class="auth-alert" role="alert"><?= escape($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= escape(Auth::csrfToken()) ?>">
            <input type="hidden" name="return" value="<?= escape($return) ?>">
            <label>Correo electrónico<input name="email" type="email" autocomplete="email" value="<?= escape($email) ?>" required></label>
            <label>Contraseña<input name="password" type="password" autocomplete="current-password" required></label>
            <button class="primary-button" type="submit">Ingresar</button>
        </form>
        <a class="auth-alternative" href="register.php">No tengo una cuenta</a>
    </section>
</main>
</body>
</html>
