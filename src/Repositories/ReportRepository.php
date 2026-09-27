<?php

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use PDO;
use RuntimeException;

final class ReportRepository
{
    private const STATUSES = ['all', 'pending', 'confirmed', 'cancelled', 'completed'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function reservations(string $from, string $to, string $status = 'all'): array
    {
        $this->validate($from, $to, $status);
        $sql = "SELECT reservations.reservation_code, reservations.check_in, reservations.check_out,
                       reservations.guests, reservations.total_amount, reservations.deposit_amount,
                       reservations.status, users.full_name, users.email, rooms.room_number,
                       room_types.name AS category
                FROM reservations
                INNER JOIN users ON users.id = reservations.user_id
                INNER JOIN rooms ON rooms.id = reservations.room_id
                INNER JOIN room_types ON room_types.id = rooms.room_type_id
                WHERE reservations.check_in BETWEEN :date_from AND :date_to";
        $parameters = ['date_from' => $from, 'date_to' => $to];
        if ($status !== 'all') {
            $sql .= ' AND reservations.status = :status';
            $parameters['status'] = $status;
        }
        $sql .= ' ORDER BY reservations.check_in, reservations.reservation_code';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function summary(array $rows): array
    {
        return [
            'reservations' => count($rows),
            'guests' => array_sum(array_map(static fn (array $row): int => (int) $row['guests'], $rows)),
            'total' => array_sum(array_map(static fn (array $row): float => (float) $row['total_amount'], $rows)),
            'deposits' => array_sum(array_map(static fn (array $row): float => (float) $row['deposit_amount'], $rows)),
        ];
    }

    private function validate(string $from, string $to, string $status): void
    {
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', $to);
        if ($start === false || $end === false || $end < $start) {
            throw new RuntimeException('Selecciona un rango de fechas válido.');
        }
        if (!in_array($status, self::STATUSES, true)) {
            throw new RuntimeException('El estado seleccionado no es válido.');
        }
    }
}
