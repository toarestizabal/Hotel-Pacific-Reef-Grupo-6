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
$values = [
    'full_name' => trim((string) ($_POST['full_name'] ?? '')),
    'email' => trim((string) ($_POST['email'] ?? '')),
    'preferred_language' => (string) ($_POST['preferred_language'] ?? I18n::language()),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!Auth::validCsrf($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('La sesión del formulario expiró. Vuelve a intentarlo.');
        }
        if ((string) ($_POST['password'] ?? '') !== (string) ($_POST['password_confirmation'] ?? '')) {
            throw new RuntimeException('Las contraseñas no coinciden.');
        }
        $user = (new UserRepository(Connection::create()))->register($_POST);
        Auth::login($user);
        header('Location: /index.php?registered=1');
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
    <title>Crear una cuenta | Hotel Pacific Reef</title>
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
    <section class="auth-card" aria-labelledby="registerTitle">
        <p class="section-kicker">Mi cuenta</p>
        <h1 id="registerTitle">Crear una cuenta</h1>
        <p>Regístrate para acceder al sistema de reservas.</p>
        <?php if ($error !== null): ?><div class="auth-alert" role="alert"><?= escape($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= escape(Auth::csrfToken()) ?>">
            <label>Nombre completo<input name="full_name" autocomplete="name" value="<?= escape($values['full_name']) ?>" required></label>
            <label>Correo electrónico<input name="email" type="email" autocomplete="email" value="<?= escape($values['email']) ?>" required></label>
            <label>Contraseña<input name="password" type="password" minlength="8" autocomplete="new-password" required></label>
            <label>Confirmar contraseña<input name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></label>
            <label>Idioma preferido<select name="preferred_language"><option value="es" <?= $values['preferred_language'] === 'es' ? 'selected' : '' ?>>Español</option><option value="en" <?= $values['preferred_language'] === 'en' ? 'selected' : '' ?>>Inglés</option></select></label>
            <button class="primary-button" type="submit">Registrarme</button>
        </form>
        <a class="auth-alternative" href="login.php">Ya tengo una cuenta</a>
    </section>
</main>
</body>
</html>
