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

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params([
            'path' => '/',
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
        self::refresh($user);
    }

    public static function refresh(array $user): void
    {
        self::start();
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
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);
        session_destroy();
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
