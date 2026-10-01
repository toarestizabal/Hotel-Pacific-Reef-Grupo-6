<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Database\Connection;
use App\Repositories\RoomRepository;
use App\Support\I18n;

require dirname(__DIR__) . '/_bootstrap.php';
requireUserRole('administrator');

$error = null;
$types = [];
try {
    $repository = new RoomRepository(Connection::create());
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::validCsrf($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('La sesión del formulario expiró.');
        }

        $roomTypeId = filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($roomTypeId === false || $price === false) {
            throw new RuntimeException('Selecciona una categoría y un precio válidos.');
        }

        $repository->updateRoomTypePrice($roomTypeId, $price);
        header('Location: prices.php?updated=1');
        exit;
    }
    $types = $repository->roomTypes();
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?= I18n::language() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Precios | Hotel Pacific Reef</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<?php renderAdminHeader('prices'); ?>
<main>
    <section class="page-heading">
        <div>
            <p>Catálogo</p>
            <h1>Precios por categoría</h1>
            <span>El precio actualizado se refleja automáticamente en el sitio público.</span>
        </div>
    </section>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice success">Precio actualizado correctamente.</div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div class="notice error"><?= escape($error) ?></div>
    <?php endif; ?>

    <section class="price-grid">
        <?php foreach ($types as $type): ?>
            <article class="panel price-card">
                <div class="panel-heading">
                    <span>Categoría</span>
                    <h2><?= escape($type['name']) ?></h2>
                </div>
                <p><?= escape($type['description']) ?></p>
                <dl>
                    <div>
                        <dt>Capacidad máxima</dt>
                        <dd><?= (int) $type['max_guests'] ?> huéspedes</dd>
                    </div>
                    <div>
                        <dt>Precio actual</dt>
                        <dd><?= money((float) $type['base_price']) ?></dd>
                    </div>
                </dl>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= escape(Auth::csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $type['id'] ?>">
                    <label>
                        Nuevo precio diario
                        <input
                            type="number"
                            name="price"
                            min="1"
                            step="1000"
                            value="<?= (int) $type['base_price'] ?>"
                            required
                        >
                    </label>
                    <button type="submit">Guardar precio</button>
                </form>
            </article>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html>
