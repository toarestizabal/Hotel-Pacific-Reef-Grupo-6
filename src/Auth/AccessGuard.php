<?php

declare(strict_types=1);

namespace App\Auth;

use App\Repositories\UserRepository;
use Throwable;

final class AccessGuard
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function requireRole(string $role): void
    {
        $this->requireAnyRole([$role]);
    }

    public function requireAnyRole(array $roles): void
    {
        $sessionUser = Auth::user();
        if ($sessionUser === null) {
            $this->redirectToLogin();
        }

        try {
            $currentUser = $this->users->find((int) $sessionUser['id']);
        } catch (Throwable) {
            http_response_code(503);
            echo 'No fue posible verificar los permisos de acceso.';
            exit;
        }

        if ($currentUser === null || !(bool) $currentUser['is_active']) {
            Auth::logout();
            $this->redirectToLogin();
        }

        Auth::refresh($currentUser);
        if (!in_array($currentUser['role'], $roles, true)) {
            http_response_code(403);
            echo 'No tienes permisos para acceder a esta página.';
            exit;
        }
    }

    private function redirectToLogin(): never
    {
        $return = rawurlencode((string) ($_SERVER['REQUEST_URI'] ?? '/index.php'));
        header('Location: /login.php?return=' . $return);
        exit;
    }
}
