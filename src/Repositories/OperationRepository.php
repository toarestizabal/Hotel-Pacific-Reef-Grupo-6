<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\DateRange;
use PDO;

final class OperationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function schedule(string $from, string $to): array
    {
        $this->validateRange($from, $to);
        $statement = $this->pdo->prepare(
            "SELECT reservations.id, reservations.reservation_code, reservations.check_in,
                    reservations.check_out, reservations.guests, reservations.status,
                    users.full_name, users.email, rooms.room_number,
                    room_types.name AS category,
                    COALESCE(GROUP_CONCAT(CONCAT(services.name, ' × ', reservation_services.quantity)
                        ORDER BY services.name SEPARATOR ', '), 'Sin servicios adicionales') AS services
             FROM reservations
             INNER JOIN users ON users.id = reservations.user_id
             INNER JOIN rooms ON rooms.id = reservations.room_id
             INNER JOIN room_types ON room_types.id = rooms.room_type_id
             LEFT JOIN reservation_services ON reservation_services.reservation_id = reservations.id
             LEFT JOIN services ON services.id = reservation_services.service_id
             WHERE reservations.status IN ('pending', 'confirmed')
               AND reservations.check_in <= :date_to
               AND reservations.check_out >= :date_from
             GROUP BY reservations.id, reservations.reservation_code, reservations.check_in,
                      reservations.check_out, reservations.guests, reservations.status,
                      users.full_name, users.email, rooms.room_number, room_types.name
             ORDER BY reservations.check_in, rooms.room_number"
        );
        $statement->execute(['date_from' => $from, 'date_to' => $to]);

        return $statement->fetchAll();
    }

    public function summary(array $reservations): array
    {
        $arrivals = 0;
        $guests = 0;
        $withServices = 0;
        foreach ($reservations as $reservation) {
            $arrivals++;
            $guests += (int) $reservation['guests'];
            if ($reservation['services'] !== 'Sin servicios adicionales') {
                $withServices++;
            }
        }

        return ['reservations' => $arrivals, 'guests' => $guests, 'with_services' => $withServices];
    }

    private function validateRange(string $from, string $to): void
    {
        DateRange::validate($from, $to, 93);
    }
}
