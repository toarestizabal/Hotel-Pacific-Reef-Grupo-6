<?php

declare(strict_types=1);

namespace App\Presentation;

final class ConfirmationEmailRenderer
{
    public function render(array $reservation, string $code, string $qrUrl, string $ticketUrl): string
    {
        $services = $reservation['services'] ?? [];
        $serviceHtml = '<p><strong>Servicios:</strong> Sin servicios adicionales.</p>';
        if ($services !== []) {
            $items = '';
            foreach ($services as $service) {
                $items .= '<li>' . self::html((string) $service['name'])
                    . ' × ' . (int) $service['quantity'] . '</li>';
            }
            $serviceHtml = '<p><strong>Servicios contratados:</strong></p><ul>' . $items . '</ul>';
        }

        $safeCode = self::html($code);
        $fullName = self::html((string) $reservation['full_name']);
        $roomNumber = self::html((string) $reservation['room_number']);
        $checkIn = self::html((string) $reservation['check_in']);
        $checkOut = self::html((string) $reservation['check_out']);
        $safeQrUrl = self::html($qrUrl);
        $safeTicketUrl = self::html($ticketUrl);
        $total = number_format((float) $reservation['total'], 0, ',', '.');
        $deposit = number_format((float) $reservation['deposit'], 0, ',', '.');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmación {$safeCode}</title>
</head>
<body style="margin:0;background:#f2f6f7;font-family:Arial,sans-serif;color:#183746">
    <main style="max-width:640px;margin:32px auto;background:white;border:1px solid #d8e2e5;border-radius:16px;padding:32px">
        <p style="color:#277b69;font-weight:700">HOTEL PACIFIC REEF</p>
        <h1>Reserva confirmada</h1>
        <p>Hola {$fullName}, tu pago de prueba fue aprobado.</p>
        <p>
            <strong>Código:</strong> {$safeCode}<br>
            <strong>Habitación:</strong> {$roomNumber}<br>
            <strong>Estadía:</strong> {$checkIn} al {$checkOut}
        </p>
        {$serviceHtml}
        <p>
            <strong>Total:</strong> &#36;{$total}<br>
            <strong>Abono:</strong> &#36;{$deposit}
        </p>
        <p style="text-align:center">
            <img src="{$safeQrUrl}" width="240" height="240" alt="Código QR de la reserva">
        </p>
        <p style="text-align:center"><a href="{$safeTicketUrl}">Consultar ticket</a></p>
        <p style="font-size:12px;color:#647985">Correo generado en el ambiente de prueba.</p>
    </main>
</body>
</html>
HTML;
    }

    private static function html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
