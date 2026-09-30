<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\JsonHttpClientInterface;
use JsonException;
use RuntimeException;
use Throwable;

final class ExchangeRateService
{
    private const ENDPOINT = 'https://open.er-api.com/v6/latest/CLP';
    private const CACHE_SECONDS = 86400;
    private const TARGET_CURRENCIES = ['USD', 'EUR'];

    public function __construct(
        private readonly JsonHttpClientInterface $httpClient,
        private readonly ?string $cacheFile = null
    ) {
    }

    public function rates(): array
    {
        $cached = $this->readCache();
        if ($cached !== null && (int) $cached['cached_at'] + self::CACHE_SECONDS > time()) {
            return $cached;
        }

        try {
            $rates = $this->requestRates();
            $this->writeCache($rates);

            return $rates;
        } catch (Throwable $exception) {
            if ($cached !== null) {
                return $cached;
            }

            throw new RuntimeException('No fue posible obtener los tipos de cambio.', 0, $exception);
        }
    }

    public function convert(float $amount): array
    {
        if ($amount < 0) {
            throw new RuntimeException('El monto a convertir no puede ser negativo.');
        }

        $data = $this->rates();

        return [
            'CLP' => $amount,
            'USD' => round($amount * (float) $data['rates']['USD'], 2),
            'EUR' => round($amount * (float) $data['rates']['EUR'], 2),
        ];
    }

    private function requestRates(): array
    {
        $response = $this->httpClient->get(self::ENDPOINT);
        if (($response['result'] ?? null) !== 'success' || ($response['base_code'] ?? null) !== 'CLP') {
            throw new RuntimeException('El proveedor no entregó tasas válidas para CLP.');
        }

        $rates = [];
        foreach (self::TARGET_CURRENCIES as $currency) {
            $rate = filter_var($response['rates'][$currency] ?? null, FILTER_VALIDATE_FLOAT);
            if ($rate === false || $rate <= 0) {
                throw new RuntimeException("El proveedor no entregó una tasa válida para {$currency}.");
            }
            $rates[$currency] = (float) $rate;
        }

        return [
            'base' => 'CLP',
            'rates' => $rates,
            'updated_at' => (string) ($response['time_last_update_utc'] ?? ''),
            'cached_at' => time(),
            'provider' => 'ExchangeRate-API',
            'provider_url' => 'https://www.exchangerate-api.com',
        ];
    }

    private function readCache(): ?array
    {
        $file = $this->cachePath();
        if (!is_file($file)) {
            return null;
        }

        try {
            $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($data)
            || ($data['base'] ?? null) !== 'CLP'
            || !isset($data['rates']['USD'], $data['rates']['EUR'], $data['cached_at'])) {
            return null;
        }

        return $data;
    }

    private function writeCache(array $data): void
    {
        $file = $this->cachePath();
        $directory = dirname($file);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return;
        }

        file_put_contents(
            $file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            LOCK_EX
        );
    }

    private function cachePath(): string
    {
        return $this->cacheFile ?? dirname(__DIR__, 2) . '/.local/cache/exchange-rates-clp.json';
    }
}
