<?php

declare(strict_types=1);

use App\Database\Connection;

$projectRoot = dirname(__DIR__);
$rooms = require $projectRoot . '/src/Data/rooms.php';
$roomGalleries = require $projectRoot . '/src/Data/room_galleries.php';

require $projectRoot . '/src/Database/Connection.php';

try {
    $statement = Connection::create()->query(
        "SELECT
            rooms.id,
            rooms.room_number AS number,
            room_types.name AS category,
            rooms.location,
            rooms.description,
            rooms.capacity,
            room_types.base_price AS price,
            rooms.equipment
         FROM rooms
         INNER JOIN room_types ON room_types.id = rooms.room_type_id
         WHERE rooms.status = 'available'
         ORDER BY rooms.room_number"
    );
    $databaseRooms = array_map(
        static function (array $room): array {
            $equipment = json_decode((string) $room['equipment'], true);
            $room['equipment'] = is_array($equipment) ? $equipment : [];
            return $room;
        },
        $statement->fetchAll()
    );

    $rooms = $databaseRooms;
} catch (Throwable) {
    // El catálogo local mantiene disponible el prototipo cuando MariaDB está apagado.
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Prototipo inicial del sistema de reservas de Hotel Pacific Reef">
    <title>Hotel Pacific Reef | Sistema de reservas</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="#inicio" aria-label="Hotel Pacific Reef, inicio">
            <span class="brand-mark" aria-hidden="true">HPR</span>
            <span class="brand-copy">
                <strong>Hotel Pacific Reef</strong>
                <small>Sistema de reservas</small>
            </span>
        </a>

        <nav class="main-nav" aria-label="Navegación principal">
            <a href="#reserva" data-i18n="navBooking">Reservar</a>
            <a href="#habitaciones" data-i18n="navRooms">Habitaciones</a>
        </nav>

        <button class="language-button" id="languageButton" type="button" aria-label="Cambiar idioma">
            ES <span>/</span> EN
        </button>
    </header>

    <main>
        <section class="overview" id="inicio">
            <div class="overview-copy">
                <p class="context-label">Reservas en línea</p>
                <h1 data-i18n="heroTitle">Hotel Pacific Reef</h1>
                <p class="overview-text" data-i18n="heroText">
                    Consulta fechas disponibles, compara nuestras habitaciones y calcula el valor de tu estadía.
                </p>
                <div class="overview-actions">
                    <a class="primary-button" href="#reserva" data-i18n="heroButton">Consultar disponibilidad</a>
                </div>
            </div>

            <aside class="property-visual" aria-label="Categorías de habitaciones">
                <div class="visual-brand">
                    <span>HPR</span>
                    <small>Hotel Pacific Reef</small>
                </div>
                <div class="visual-content">
                    <div>
                        <small>Habitación Turista</small>
                        <strong>Desde $68.000</strong>
                        <span>por noche</span>
                    </div>
                    <div>
                        <small>Habitación Premium</small>
                        <strong>Desde $125.000</strong>
                        <span>por noche</span>
                    </div>
                </div>
                <p>El abono para confirmar la reserva corresponde al 30 % del valor total.</p>
            </aside>
        </section>

        <section class="booking-section" id="reserva" aria-labelledby="bookingTitle">
            <div class="section-heading booking-heading">
                <div>
                    <span class="section-number">01</span>
                    <div>
                        <p class="section-kicker">Reserva</p>
                        <h2 id="bookingTitle" data-i18n="bookingTitle">Consulta de disponibilidad</h2>
                    </div>
                </div>
                <p>Ingresa los datos principales para obtener una estimación de la estadía.</p>
            </div>

            <form id="bookingForm" novalidate>
                <label>
                    <span data-i18n="checkIn">Llegada</span>
                    <input id="checkIn" name="check_in" type="date" required>
                </label>
                <label>
                    <span data-i18n="checkOut">Salida</span>
                    <input id="checkOut" name="check_out" type="date" required>
                </label>
                <label>
                    <span data-i18n="guests">Huéspedes</span>
                    <select id="guests" name="guests">
                        <option value="1">1 huésped</option>
                        <option value="2" selected>2 huéspedes</option>
                        <option value="3">3 huéspedes</option>
                        <option value="4">4 huéspedes</option>
                    </select>
                </label>
                <label>
                    <span data-i18n="room">Habitación</span>
                    <select id="room" name="room">
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= (int) $room['id'] ?>" data-price="<?= (int) $room['price'] ?>" data-capacity="<?= (int) $room['capacity'] ?>">
                                <?= escape($room['number'] . ' · ' . $room['category']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="primary-button form-button" type="submit" data-i18n="calculate">Calcular reserva</button>
            </form>

            <div class="booking-result" id="bookingResult" aria-live="polite">
                <span data-i18n="resultHint">Selecciona las fechas para obtener un cálculo preliminar.</span>
            </div>
        </section>

        <section class="rooms-section" id="habitaciones" aria-labelledby="roomsTitle">
            <div class="section-heading">
                <div>
                    <span class="section-number">02</span>
                    <div>
                        <p class="section-kicker">Catálogo</p>
                        <h2 id="roomsTitle" data-i18n="roomsTitle">Habitaciones disponibles</h2>
                    </div>
                </div>
                <p data-i18n="roomsText">Muestra inicial de las categorías Turista y Premium definidas para el sistema.</p>
            </div>

            <div class="room-grid">
                <?php foreach ($rooms as $index => $room): ?>
                    <?php $gallery = $roomGalleries[$room['category']] ?? $roomGalleries['Turista']; ?>
                    <article class="room-card">
                        <div class="room-visual">
                            <img src="<?= escape($gallery[0]['src']) ?>" alt="<?= escape($gallery[0]['alt']) ?>" loading="lazy">
                            <small><?= escape($room['category']) ?></small>
                        </div>
                        <div class="room-body">
                            <div class="room-title-row">
                                <div>
                                    <span class="room-code"><?= escape($room['number']) ?></span>
                                    <h3>Habitación <?= escape($room['number']) ?></h3>
                                </div>
                                <div class="room-price">
                                    <strong>$<?= number_format((int) $room['price'], 0, ',', '.') ?></strong>
                                    <small>por noche</small>
                                </div>
                            </div>
                            <p class="room-location"><?= escape($room['location']) ?> · Capacidad para <?= (int) $room['capacity'] ?> personas</p>
                            <ul class="equipment-list">
                                <?php foreach ($room['equipment'] as $equipment): ?>
                                    <li><?= escape($equipment) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="room-actions">
                                <button class="secondary-button room-detail-button" type="button" data-dialog-id="roomDetail<?= (int) $room['id'] ?>">
                                    Ver detalles
                                </button>
                                <button class="secondary-button room-select-button" type="button" data-room-id="<?= (int) $room['id'] ?>">
                                    Seleccionar
                                </button>
                            </div>
                        </div>
                    </article>

                    <dialog class="room-dialog" id="roomDetail<?= (int) $room['id'] ?>" aria-labelledby="roomDialogTitle<?= (int) $room['id'] ?>">
                        <button class="dialog-close" type="button" aria-label="Cerrar detalles">×</button>
                        <div class="room-gallery">
                            <?php foreach ($gallery as $photoIndex => $photo): ?>
                                <img src="<?= escape($photo['src']) ?>" alt="<?= escape($photo['alt']) ?>" <?= $photoIndex === 0 ? '' : 'loading="lazy"' ?>>
                            <?php endforeach; ?>
                        </div>
                        <div class="dialog-content">
                            <div>
                                <span class="room-code"><?= escape($room['category']) ?> · <?= escape($room['number']) ?></span>
                                <h3 id="roomDialogTitle<?= (int) $room['id'] ?>">Habitación <?= escape($room['number']) ?></h3>
                                <p><?= escape((string) ($room['description'] ?? 'Habitación equipada para una estadía cómoda.')) ?></p>
                                <p><?= escape($room['location']) ?> · Capacidad para <?= (int) $room['capacity'] ?> personas</p>
                            </div>
                            <div class="dialog-price">
                                <strong>$<?= number_format((int) $room['price'], 0, ',', '.') ?></strong>
                                <small>por noche</small>
                            </div>
                        </div>
                        <ul class="equipment-list dialog-equipment">
                            <?php foreach ($room['equipment'] as $equipment): ?>
                                <li><?= escape($equipment) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button class="primary-button room-select-button dialog-select" type="button" data-room-id="<?= (int) $room['id'] ?>">
                            Seleccionar esta habitación
                        </button>
                    </dialog>
                <?php endforeach; ?>
            </div>
        </section>

    </main>

    <footer>
        <div>
            <strong>Hotel Pacific Reef</strong>
            <span>Sistema de Gestión de Reserva Hotelera</span>
        </div>
    </footer>
</body>
</html>
