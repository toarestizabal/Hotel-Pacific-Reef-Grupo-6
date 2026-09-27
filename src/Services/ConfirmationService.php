<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class ConfirmationService
{
    public function __construct(private readonly ?string $outboxDirectory = null)
    {
    }

    public function deliverToTestOutbox(array $reservation, string $baseUrl): array
    {
        $code = $this->safeCode((string) $reservation['code']);
        $token = $this->safeToken((string) ($reservation['verification_token'] ?? ''));
        $baseUrl = rtrim($baseUrl, '/');
        $ticketUrl = $baseUrl . '/ticket.php?token=' . rawurlencode($token);
        $qrUrl = $baseUrl . '/qr.php?token=' . rawurlencode($token);
        $directory = $this->directory();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('No fue posible preparar la bandeja de correo de prueba.');
        }

        $services = $reservation['services'] ?? [];
        $serviceHtml = '<p><strong>Servicios:</strong> Sin servicios adicionales.</p>';
        if ($services !== []) {
            $items = '';
            foreach ($services as $service) {
                $items .= '<li>' . self::html((string) $service['name']) . ' × ' . (int) $service['quantity'] . '</li>';
            }
            $serviceHtml = '<p><strong>Servicios contratados:</strong></p><ul>' . $items . '</ul>';
        }

        $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Confirmación ' . self::html($code) . '</title></head>'
            . '<body style="margin:0;background:#f2f6f7;font-family:Arial,sans-serif;color:#183746"><main style="max-width:640px;margin:32px auto;background:white;border:1px solid #d8e2e5;border-radius:16px;padding:32px">'
            . '<p style="color:#277b69;font-weight:700">HOTEL PACIFIC REEF</p><h1>Reserva confirmada</h1>'
            . '<p>Hola ' . self::html((string) $reservation['full_name']) . ', tu pago de prueba fue aprobado.</p>'
            . '<p><strong>Código:</strong> ' . self::html($code) . '<br><strong>Habitación:</strong> ' . self::html((string) $reservation['room_number'])
            . '<br><strong>Estadía:</strong> ' . self::html((string) $reservation['check_in']) . ' al ' . self::html((string) $reservation['check_out']) . '</p>'
            . $serviceHtml . '<p><strong>Total:</strong> $' . number_format((float) $reservation['total'], 0, ',', '.') . '<br><strong>Abono:</strong> $' . number_format((float) $reservation['deposit'], 0, ',', '.') . '</p>'
            . '<p style="text-align:center"><img src="' . self::html($qrUrl) . '" width="240" height="240" alt="Código QR de la reserva"></p>'
            . '<p style="text-align:center"><a href="' . self::html($ticketUrl) . '">Consultar ticket</a></p>'
            . '<p style="font-size:12px;color:#647985">Correo generado en el ambiente de prueba de la Semana 7.</p></main></body></html>';

        if (file_put_contents($this->file($code), $html, LOCK_EX) === false) {
            throw new RuntimeException('No fue posible generar el correo de confirmación de prueba.');
        }

        return [
            'qr_url' => $qrUrl,
            'ticket_url' => $ticketUrl,
            'mail_preview_url' => '/mail-preview.php?code=' . rawurlencode($code),
            'delivery' => 'test_outbox',
        ];
    }

    public function read(string $code): ?string
    {
        $path = $this->file($this->safeCode($code));
        if (!is_file($path)) {
            return null;
        }
        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }

    private function directory(): string
    {
        return $this->outboxDirectory ?? dirname(__DIR__, 2) . '/.local/mail';
    }

    private function file(string $code): string
    {
        return $this->directory() . DIRECTORY_SEPARATOR . $code . '.html';
    }

    private function safeCode(string $code): string
    {
        $code = strtoupper(trim($code));
        if (!preg_match('/^HPR-[A-Z0-9-]{6,24}$/', $code)) {
            throw new RuntimeException('El código de reserva no es válido.');
        }

        return $code;
    }

    private function safeToken(string $token): string
    {
        $token = strtolower(trim($token));
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new RuntimeException('El token de verificación de la reserva no es válido.');
        }

        return $token;
    }

    private static function html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
