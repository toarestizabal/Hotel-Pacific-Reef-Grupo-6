<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;

final class UserRepository
{
    private const ROLES = ['client', 'worker', 'administrator'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function register(array $data, string $role = 'client'): array
    {
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $language = in_array(($data['preferred_language'] ?? 'es'), ['es', 'en'], true)
            ? (string) $data['preferred_language']
            : 'es';

        if (strlen($fullName) < 3) {
            throw new RuntimeException('Ingresa un nombre completo válido.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Ingresa un correo electrónico válido.');
        }
        if (strlen($password) < 8) {
            throw new RuntimeException('La contraseña debe tener al menos 8 caracteres.');
        }
        if (!in_array($role, self::ROLES, true)) {
            throw new RuntimeException('El rol seleccionado no es válido.');
        }

        $exists = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $exists->execute(['email' => $email]);
        if ((int) $exists->fetchColumn() > 0) {
            throw new RuntimeException('El correo ya está registrado.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, preferred_language, is_active)
             VALUES (:full_name, :email, :password_hash, :role, :preferred_language, 1)'
        );
        $statement->execute([
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'preferred_language' => $language,
        ]);

        return $this->find((int) $this->pdo->lastInsertId()) ?? throw new RuntimeException('No fue posible crear la cuenta.');
    }

    public function authenticate(string $email, string $password): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();

        if ($user === false || !(bool) $user['is_active'] || !password_verify($password, (string) $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, full_name, email, role, preferred_language, is_active, created_at
             FROM users WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function all(): array
    {
        return $this->pdo->query(
            "SELECT id, full_name, email, role, preferred_language, is_active, created_at
             FROM users
             ORDER BY FIELD(role, 'administrator', 'worker', 'client'), full_name"
        )->fetchAll();
    }

    public function updateAccess(int $id, string $role, bool $isActive, int $currentUserId): void
    {
        if (!in_array($role, self::ROLES, true)) {
            throw new RuntimeException('El rol seleccionado no es válido.');
        }
        $current = $this->find($id);
        if ($current === null) {
            throw new RuntimeException('La cuenta seleccionada no existe.');
        }
        if ($id === $currentUserId && (!$isActive || $role !== 'administrator')) {
            throw new RuntimeException('No puedes quitar tu propio acceso de administrador.');
        }

        if ($current['role'] === 'administrator' && ($role !== 'administrator' || !$isActive)) {
            $activeAdministrators = (int) $this->pdo->query(
                "SELECT COUNT(*) FROM users WHERE role = 'administrator' AND is_active = 1"
            )->fetchColumn();
            if ($activeAdministrators <= 1) {
                throw new RuntimeException('Debe permanecer al menos un administrador activo.');
            }
        }

        $statement = $this->pdo->prepare('UPDATE users SET role = :role, is_active = :is_active WHERE id = :id');
        $statement->execute(['role' => $role, 'is_active' => $isActive ? 1 : 0, 'id' => $id]);
    }

    public function updateLanguage(int $id, string $language): void
    {
        if (!in_array($language, ['es', 'en'], true)) {
            return;
        }
        $statement = $this->pdo->prepare('UPDATE users SET preferred_language = :language WHERE id = :id');
        $statement->execute(['language' => $language, 'id' => $id]);
    }

    public function upsertAdministrator(string $fullName, string $email, string $password): array
    {
        $statement = $this->pdo->prepare('SELECT id FROM users WHERE email = :email');
        $statement->execute(['email' => strtolower(trim($email))]);
        $id = $statement->fetchColumn();
        if ($id === false) {
            return $this->register([
                'full_name' => $fullName,
                'email' => $email,
                'password' => $password,
                'preferred_language' => 'es',
            ], 'administrator');
        }

        if (strlen($password) < 8) {
            throw new RuntimeException('La contraseña debe tener al menos 8 caracteres.');
        }
        $update = $this->pdo->prepare(
            "UPDATE users
             SET full_name = :full_name, password_hash = :password_hash,
                 role = 'administrator', is_active = 1
             WHERE id = :id"
        );
        $update->execute([
            'full_name' => trim($fullName),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => (int) $id,
        ]);

        return $this->find((int) $id) ?? throw new RuntimeException('No fue posible actualizar la cuenta.');
    }
}
