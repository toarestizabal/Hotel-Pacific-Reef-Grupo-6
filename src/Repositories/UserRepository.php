<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;
use RuntimeException;

final class UserRepository
{
    private const ROLES = ['client', 'worker', 'administrator'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function register(array $data, string $role = 'client'): array
    {
        $fullName = $this->validFullName((string) ($data['full_name'] ?? ''));
        $email = $this->validEmail((string) ($data['email'] ?? ''));
        $password = $this->validPassword((string) ($data['password'] ?? ''));
        $language = in_array(($data['preferred_language'] ?? 'es'), ['es', 'en'], true)
            ? (string) $data['preferred_language']
            : 'es';

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
        try {
            $statement->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'preferred_language' => $language,
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new RuntimeException('El correo ya está registrado.', 0, $exception);
            }
            throw $exception;
        }

        return $this->find((int) $this->pdo->lastInsertId()) ?? throw new RuntimeException('No fue posible crear la cuenta.');
    }

    public function authenticate(string $email, string $password): ?array
    {
        $email = strtolower(trim($email));
        if (strlen($email) > 190 || strlen($password) > 128) {
            return null;
        }

        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
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
        return $this->upsertRoleAccount($fullName, $email, $password, 'administrator');
    }

    public function upsertWorker(string $fullName, string $email, string $password): array
    {
        return $this->upsertRoleAccount($fullName, $email, $password, 'worker');
    }

    private function upsertRoleAccount(string $fullName, string $email, string $password, string $role): array
    {
        if (!in_array($role, self::ROLES, true) || $role === 'client') {
            throw new RuntimeException('El rol de demostración no es válido.');
        }
        $fullName = $this->validFullName($fullName);
        $email = $this->validEmail($email);
        $password = $this->validPassword($password);

        $statement = $this->pdo->prepare('SELECT id FROM users WHERE email = :email');
        $statement->execute(['email' => $email]);
        $id = $statement->fetchColumn();
        if ($id === false) {
            return $this->register([
                'full_name' => $fullName,
                'email' => $email,
                'password' => $password,
                'preferred_language' => 'es',
            ], $role);
        }

        $update = $this->pdo->prepare(
            'UPDATE users
             SET full_name = :full_name, password_hash = :password_hash,
                 role = :role, is_active = 1
             WHERE id = :id'
        );
        $update->execute([
            'full_name' => $fullName,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'id' => (int) $id,
        ]);

        return $this->find((int) $id) ?? throw new RuntimeException('No fue posible actualizar la cuenta.');
    }

    private function validFullName(string $fullName): string
    {
        $fullName = trim($fullName);
        $length = mb_strlen($fullName);
        if ($length < 3 || $length > 120) {
            throw new RuntimeException('Ingresa un nombre completo válido de hasta 120 caracteres.');
        }

        return $fullName;
    }

    private function validEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Ingresa un correo electrónico válido.');
        }

        return $email;
    }

    private function validPassword(string $password): string
    {
        $length = strlen($password);
        if ($length < 8 || $length > 128) {
            throw new RuntimeException('La contraseña debe tener entre 8 y 128 caracteres.');
        }

        return $password;
    }
}
