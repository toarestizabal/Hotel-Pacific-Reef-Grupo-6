<?php

declare(strict_types=1);

namespace App\Http;

use App\Contracts\JsonHttpClientInterface;
use JsonException;
use RuntimeException;

final class NativeJsonHttpClient implements JsonHttpClientInterface
{
    public function __construct(private readonly int $timeoutSeconds = 5)
    {
    }

    public function get(string $url): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
            throw new RuntimeException('La URL del servicio externo no es válida.');
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: Hotel-Pacific-Reef/1.0\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        $statusCode = $this->statusCode($http_response_header ?? []);

        if ($body === false || $statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('El servicio externo no respondió correctamente.');
        }

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('El servicio externo devolvió una respuesta inválida.', 0, $exception);
        }

        if (!is_array($data)) {
            throw new RuntimeException('El servicio externo devolvió una respuesta inválida.');
        }

        return $data;
    }

    private function statusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}
