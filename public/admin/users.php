<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Database\Connection;
use App\Repositories\UserRepository;
use App\Support\I18n;

require dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/Database/Connection.php';
require_once dirname(__DIR__, 2) . '/src/Repositories/UserRepository.php';
Auth::requireRole('administrator');

$error = null;
$users = [];
$roles = ['client' => 'Cliente', 'worker' => 'Trabajador', 'administrator' => 'Administrador'];

try {
    $repository = new UserRepository(Connection::create());
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::validCsrf($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('La sesión del formulario expiró. Vuelve a intentarlo.');
        }
        $repository->updateAccess(
            (int) ($_POST['id'] ?? 0),
            (string) ($_POST['role'] ?? ''),
            isset($_POST['is_active']),
            (int) Auth::user()['id']
        );
        header('Location: users.php?updated=1');
        exit;
    }
    $users = $repository->all();
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?= I18n::language() ?>">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Usuarios | Hotel Pacific Reef</title><link rel="stylesheet" href="../assets/css/admin.css"><link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php renderAdminHeader('users'); ?>
<main>
    <section class="page-heading"><div><p>Administración</p><h1>Gestión de usuarios</h1><span>Administra los roles y el estado de acceso de cada cuenta.</span></div></section>
    <?php if (isset($_GET['updated'])): ?><div class="notice success">Usuario actualizado correctamente.</div><?php endif; ?>
    <?php if ($error !== null): ?><div class="notice error"><?= escape($error) ?></div><?php endif; ?>
    <section class="panel table-panel"><div class="panel-heading"><span>Cuentas y permisos</span><h2>Usuarios</h2></div><div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Correo electrónico</th><th>Idioma</th><th>Rol y estado</th></tr></thead><tbody>
    <?php foreach ($users as $user): ?><tr><td><strong><?= escape($user['full_name']) ?></strong><small><?= escape((string) $user['created_at']) ?></small></td><td><?= escape($user['email']) ?></td><td><?= strtoupper(escape($user['preferred_language'])) ?></td><td><form class="user-access-form" method="post"><input type="hidden" name="csrf_token" value="<?= escape(Auth::csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int) $user['id'] ?>"><select name="role" aria-label="Rol de <?= escape($user['full_name']) ?>"><?php foreach ($roles as $value => $label): ?><option value="<?= $value ?>" <?= $user['role'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select><label class="active-check"><input type="checkbox" name="is_active" value="1" <?= (bool) $user['is_active'] ? 'checked' : '' ?>> Activo</label><button type="submit">Guardar cambios</button></form></td></tr><?php endforeach; ?>
    <?php if ($users === []): ?><tr><td class="empty" colspan="4">No existen usuarios registrados.</td></tr><?php endif; ?>
    </tbody></table></div></section>
</main>
</body>
</html>
