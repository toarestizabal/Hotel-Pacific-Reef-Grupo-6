<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Support\I18n;

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/src/Auth/Auth.php';
require_once $projectRoot . '/src/Support/I18n.php';

Auth::start();
I18n::boot();
I18n::beginOutputTranslation();

if (!function_exists('escape')) {
    function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('languageUrl')) {
    function languageUrl(string $language): string
    {
        $return = (string) ($_SERVER['REQUEST_URI'] ?? '/index.php');
        return '/language.php?lang=' . rawurlencode($language) . '&return=' . rawurlencode($return);
    }
}

if (!function_exists('renderAdminHeader')) {
    function renderAdminHeader(string $active): void
    {
        $links = [
            'index' => ['index.php', 'Resumen'],
            'rooms' => ['rooms.php', 'Habitaciones'],
            'prices' => ['prices.php', 'Precios'],
            'reservations' => ['reservations.php', 'Reservas'],
            'users' => ['users.php', 'Usuarios'],
        ];
        echo '<header class="admin-header"><div><span>HPR</span><div><strong>Hotel Pacific Reef</strong><small>Administración</small></div></div>';
        echo '<nav aria-label="Navegación administrativa">';
        foreach ($links as $key => [$href, $label]) {
            $class = $key === $active ? ' class="active"' : '';
            echo '<a' . $class . ' href="' . $href . '">' . $label . '</a>';
        }
        echo '<a href="../index.php">Sitio público</a>';
        echo '<a href="' . escape(languageUrl(\App\Support\I18n::language() === 'es' ? 'en' : 'es')) . '">ES / EN</a>';
        echo '<form class="nav-form" action="../logout.php" method="post"><input type="hidden" name="csrf_token" value="' . escape(\App\Auth\Auth::csrfToken()) . '"><button type="submit">Cerrar sesión</button></form>';
        echo '</nav></header>';
    }
}
