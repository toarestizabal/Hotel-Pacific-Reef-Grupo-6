<?php

declare(strict_types=1);

namespace App\Services;

use App\Presentation\ConfirmationEmailRenderer;
use RuntimeException;

final class ConfirmationService
{
    private readonly ConfirmationEmailRenderer $renderer;

    public function __construct(
        private readonly ?string $outboxDirectory = null,
        ?ConfirmationEmailRenderer $renderer = null
    ) {
        $this->renderer = $renderer ?? new ConfirmationEmailRenderer();
    }

    public function deliverToTestOutbox(array $reservation, string $baseUrl): array
    {
        $links = $this->links($reservation, $baseUrl);
        $code = $this->safeCode((string) $reservation['code']);
        $ticketUrl = $links['ticket_url'];
        $qrUrl = $links['qr_url'];
        $directory = $this->directory();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('No fue posible preparar la bandeja de correo de prueba.');
        }

        $html = $this->renderer->render($reservation, $code, $qrUrl, $ticketUrl);

        if (file_put_contents($this->file($code), $html, LOCK_EX) === false) {
            throw new RuntimeException('No fue posible generar el correo de confirmación de prueba.');
        }

        return [...$links, 'delivery' => 'test_outbox'];
    }

    public function links(array $reservation, string $baseUrl): array
    {
        $code = $this->safeCode((string) $reservation['code']);
        $token = $this->safeToken((string) ($reservation['verification_token'] ?? ''));
        $baseUrl = rtrim($baseUrl, '/');

        return [
            'qr_url' => $baseUrl . '/qr.php?token=' . rawurlencode($token),
            'ticket_url' => $baseUrl . '/ticket.php?token=' . rawurlencode($token),
            'mail_preview_url' => '/mail-preview.php?code=' . rawurlencode($code),
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

}
