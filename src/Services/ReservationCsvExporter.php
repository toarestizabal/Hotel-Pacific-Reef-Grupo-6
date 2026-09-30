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
                $row['reservation_code'],
                $row['full_name'],
                $row['email'],
                $row['room_number'],
                $row['category'],
                $row['check_in'],
                $row['check_out'],
                $row['guests'],
                $row['total_amount'],
                $row['deposit_amount'],
                $row['status'],
            ], ';');
        }
    }
}
