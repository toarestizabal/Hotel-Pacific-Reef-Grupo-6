<?php

declare(strict_types=1);

namespace App\Support;

final class View
{
    public static function escape(string|int|float $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function money(float $value): string
    {
        return '$' . number_format($value, 0, ',', '.');
    }

    public static function equipment(string $json): string
    {
        $items = json_decode($json, true);

        return is_array($items) ? implode(', ', $items) : '';
    }

    public static function languageUrl(string $language, string $returnPath): string
    {
        return '/language.php?lang=' . rawurlencode($language) . '&return=' . rawurlencode($returnPath);
    }

    public static function applicationUrl(string $configuredUrl, array $server): string
    {
        $configuredUrl = rtrim(trim($configuredUrl), '/');
        if ($configuredUrl !== '' && filter_var($configuredUrl, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($configuredUrl, PHP_URL_SCHEME));
            if (in_array($scheme, ['http', 'https'], true)) {
                return $configuredUrl;
            }
        }

        $host = (string) ($server['HTTP_HOST'] ?? 'localhost:8000');
        if (!preg_match('/^[A-Za-z0-9.:-]+$/', $host)) {
            $host = 'localhost:8000';
        }
        $scheme = (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') ? 'https' : 'http';

        return $scheme . '://' . $host;
    }
}
