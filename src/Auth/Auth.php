<?php

declare(strict_types=1);

namespace App\Auth;

final class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }

    public static function user(): ?array
    {
        self::start();
        $user = $_SESSION['auth_user'] ?? null;

        return is_array($user) ? $user : null;
    }

    public static function login(array $user): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['auth_user'] = [
            'id' => (int) $user['id'],
            'full_name' => (string) $user['full_name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'preferred_language' => (string) ($user['preferred_language'] ?? 'es'),
        ];
        $_SESSION['language'] = (string) ($user['preferred_language'] ?? 'es');
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function requireRole(string $role): void
    {
        $user = self::user();
        if ($user === null) {
            $return = rawurlencode((string) ($_SERVER['REQUEST_URI'] ?? '/admin/index.php'));
            header('Location: /login.php?return=' . $return);
            exit;
        }

        if ($user['role'] !== $role) {
            http_response_code(403);
            echo 'No tienes permisos para acceder a esta página.';
            exit;
        }
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    public static function validCsrf(?string $token): bool
    {
        return is_string($token) && hash_equals(self::csrfToken(), $token);
    }
}
