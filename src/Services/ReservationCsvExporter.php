<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class ReservationCsvExporter
{
    private const HEADERS = [
        'Código',
        'Cliente',
        'Correo',
        'Habitación',
        'Categoría',
        'Llegada',
        'Salida',
        'Huéspedes',
        'Total',
        'Abono',
        'Estado',
    ];

    public function write($stream, array $rows): void
    {
        if (!is_resource($stream)) {
            throw new RuntimeException('No fue posible abrir el archivo CSV.');
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, self::HEADERS, ';');

        foreach ($rows as $row) {
            fputcsv($stream, [
                $this->safeCell($row['reservation_code']),
                $this->safeCell($row['full_name']),
                $this->safeCell($row['email']),
                $this->safeCell($row['room_number']),
                $this->safeCell($row['category']),
                $this->safeCell($row['check_in']),
                $this->safeCell($row['check_out']),
                $this->safeCell($row['guests']),
                $this->safeCell($row['total_amount']),
                $this->safeCell($row['deposit_amount']),
                $this->safeCell($row['status']),
            ], ';');
        }
    }

    private function safeCell(string|int|float $value): string
    {
        $cell = (string) $value;

        return preg_match('/^[=+\-@]/', ltrim($cell)) === 1 ? "'" . $cell : $cell;
    }
}
