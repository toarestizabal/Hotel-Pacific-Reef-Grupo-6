<?php

declare(strict_types=1);

use App\Contracts\JsonHttpClientInterface;
use App\Database\Connection;
use App\Repositories\RoomRepository;
use App\Services\AvailabilityService;
use App\Services\ExchangeRateService;

require dirname(__DIR__) . '/src/autoload.php';

$availability = new AvailabilityService(new RoomRepository(Connection::create()));
$checkIn = date('Y-m-d', strtotime('+500 days'));
$checkOut = date('Y-m-d', strtotime('+503 days'));
$result = $availability->search($checkIn, $checkOut, 2);

if ($result['nights'] !== 3 || $result['rooms'] === []) {
    throw new RuntimeException('La API propia no entregó disponibilidad válida.');
}
foreach ($result['rooms'] as $room) {
    if ((int) $room['capacity'] < 2 || (float) $room['daily_rate'] <= 0) {
        throw new RuntimeException('La API propia entregó una habitación inválida.');
    }
}

foreach (['2abc', '2.9'] as $invalidGuests) {
    try {
        $availability->search($checkIn, $checkOut, $invalidGuests);
        throw new RuntimeException('La API aceptó una cantidad de huéspedes mal formada.');
    } catch (InvalidArgumentException) {
        // Resultado esperado.
    }
}

$httpClient = new class implements JsonHttpClientInterface {
    public int $requests = 0;

    public function get(string $url): array
    {
        $this->requests++;

        return [
            'result' => 'success',
            'base_code' => 'CLP',
            'time_last_update_utc' => 'Wed, 30 Sep 2026 00:02:31 +0000',
            'rates' => ['USD' => 0.001034, 'EUR' => 0.000911],
        ];
    }
};
$cacheFile = sys_get_temp_dir() . '/hpr-exchange-' . bin2hex(random_bytes(6)) . '.json';

try {
    $exchange = new ExchangeRateService($httpClient, $cacheFile);
    $converted = $exchange->convert(100000);
    $exchange->rates();

    if ($converted['USD'] !== 103.40 || $converted['EUR'] !== 91.10) {
        throw new RuntimeException('La conversión de moneda no entregó los valores esperados.');
    }
    if ($httpClient->requests !== 1) {
        throw new RuntimeException('La caché no evitó solicitudes externas duplicadas.');
    }

    echo "API DE DISPONIBILIDAD: OK\n";
    echo "VALIDACIÓN ESTRICTA DE PARÁMETROS: OK\n";
    echo "SERVICIO EXTERNO DE MONEDAS: OK\n";
    echo "CACHÉ DE TASAS: OK\n";
} finally {
    if (is_file($cacheFile)) {
        unlink($cacheFile);
    }
}
