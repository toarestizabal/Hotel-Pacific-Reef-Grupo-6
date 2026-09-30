<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Http\NativeJsonHttpClient;
use App\Services\ExchangeRateService;
use App\Support\I18n;
use App\Support\View;

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/src/autoload.php';

Auth::start();
I18n::boot();
I18n::beginOutputTranslation();

if (!function_exists('escape')) {
    function escape(string $value): string
    {
        return View::escape($value);
    }
}

if (!function_exists('money')) {
    function money(float $value): string
    {
        return View::money($value);
    }
}

if (!function_exists('foreignMoney')) {
    function foreignMoney(float $value, string $currency): string
    {
        return View::foreignMoney($value, $currency);
    }
}

if (!function_exists('referenceExchangeRates')) {
    /** @return array{USD: float, EUR: float} */
    function referenceExchangeRates(): array
    {
        static $rates = null;

        if ($rates !== null) {
            return $rates;
        }

        $rates = ['USD' => 0.0, 'EUR' => 0.0];

        try {
            $exchangeData = (new ExchangeRateService(new NativeJsonHttpClient()))->rates();
            $rates['USD'] = (float) ($exchangeData['rates']['USD'] ?? 0);
            $rates['EUR'] = (float) ($exchangeData['rates']['EUR'] ?? 0);
        } catch (Throwable) {
            // Las equivalencias son informativas y nunca deben bloquear la aplicación.
        }

        return $rates;
    }
}

if (!function_exists('equipmentText')) {
    function equipmentText(string $json): string
    {
        return View::equipment($json);
    }
}

if (!function_exists('languageUrl')) {
    function languageUrl(string $language): string
    {
        $return = (string) ($_SERVER['REQUEST_URI'] ?? '/index.php');

        return View::languageUrl($language, $return);
    }
}

if (!function_exists('applicationUrl')) {
    function applicationUrl(): string
    {
        return View::applicationUrl((string) getenv('APP_URL'), $_SERVER);
    }
}

if (!function_exists('renderAdminHeader')) {
    function renderAdminHeader(string $active): void
    {
        require __DIR__ . '/partials/admin_header.php';
    }
}
