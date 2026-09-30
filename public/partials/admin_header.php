<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Support\I18n;

$links = [
    'index' => ['index.php', 'Resumen'],
    'rooms' => ['rooms.php', 'Habitaciones'],
    'prices' => ['prices.php', 'Precios'],
    'reservations' => ['reservations.php', 'Reservas'],
    'reports' => ['reports.php', 'Reportes'],
    'users' => ['users.php', 'Usuarios'],
];
?>
<header class="admin-header">
    <div><span>HPR</span><div><strong>Hotel Pacific Reef</strong><small>Administración</small></div></div>
    <nav aria-label="Navegación administrativa">
        <?php foreach ($links as $key => [$href, $label]): ?>
            <a<?= $key === $active ? ' class="active"' : '' ?> href="<?= escape($href) ?>"><?= escape($label) ?></a>
        <?php endforeach; ?>
        <a href="../index.php">Sitio público</a>
        <a href="<?= escape(languageUrl(I18n::language() === 'es' ? 'en' : 'es')) ?>">ES / EN</a>
        <form class="nav-form" action="../logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= escape(Auth::csrfToken()) ?>">
            <button type="submit">Cerrar sesión</button>
        </form>
    </nav>
</header>
