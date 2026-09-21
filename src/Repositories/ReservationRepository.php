<?php

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

final class ReservationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function room(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT rooms.*, room_types.name AS category,
                    room_types.base_price AS price
             FROM rooms
             INNER JOIN room_types ON room_types.id = rooms.room_type_id
             WHERE rooms.id = :id'
        );
        $statement->execute(['id' => $id]);
        $room = $statement->fetch();

        return $room === false ? null : $room;
    }

    public function isAvailable(int $roomId, string $checkIn, string $checkOut): bool
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM reservations
             WHERE room_id = ?
               AND status IN ('pending', 'confirmed')
               AND check_in < ?
               AND check_out > ?"
        );
        $statement->execute([$roomId, $checkOut, $checkIn]);

        return (int) $statement->fetchColumn() === 0;
    }

    public function createConfirmed(array $data, int $userId): array
    {
        $room = $this->room((int) $data['room_id']);
        if ($room === null || $room['status'] !== 'available') {
            throw new RuntimeException('La habitación seleccionada no está disponible.');
        }

        $checkIn = new DateTimeImmutable((string) $data['check_in']);
        $checkOut = new DateTimeImmutable((string) $data['check_out']);
        $nights = (int) $checkIn->diff($checkOut)->format('%r%a');
        $guests = (int) $data['guests'];

        if ($nights < 1) {
            throw new RuntimeException('La fecha de salida debe ser posterior a la fecha de llegada.');
        }
        if ($guests < 1 || $guests > (int) $room['capacity']) {
            throw new RuntimeException('La cantidad de huéspedes no es válida para esta habitación.');
        }

        $user = $this->activeUser($userId);
        if ($user === null) {
            throw new RuntimeException('Debes iniciar sesión con una cuenta activa para reservar.');
        }

        $dailyRate = (float) $room['price'];
        $total = $dailyRate * $nights;
        $deposit = round($total * 0.30, 2);
        $reservationCode = 'HPR-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $this->pdo->beginTransaction();
        try {
            if (!$this->isAvailable((int) $room['id'], $checkIn->format('Y-m-d'), $checkOut->format('Y-m-d'))) {
                throw new RuntimeException('La habitación ya fue reservada para parte del período seleccionado.');
            }

            $reservation = $this->pdo->prepare(
                "INSERT INTO reservations
                    (reservation_code, user_id, room_id, check_in, check_out, guests,
                     daily_rate, total_amount, deposit_amount, status)
                 VALUES
                    (:code, :user_id, :room_id, :check_in, :check_out, :guests,
                     :daily_rate, :total_amount, :deposit_amount, 'confirmed')"
            );
            $reservation->execute([
                'code' => $reservationCode,
                'user_id' => $userId,
                'room_id' => (int) $room['id'],
                'check_in' => $checkIn->format('Y-m-d'),
                'check_out' => $checkOut->format('Y-m-d'),
                'guests' => $guests,
                'daily_rate' => $dailyRate,
                'total_amount' => $total,
                'deposit_amount' => $deposit,
            ]);
            $reservationId = (int) $this->pdo->lastInsertId();

            $payment = $this->pdo->prepare(
                "INSERT INTO payments
                    (reservation_id, amount, method, status, external_reference, paid_at)
                 VALUES
                    (:reservation_id, :amount, 'test', 'approved', :reference, CURRENT_TIMESTAMP)"
            );
            $payment->execute([
                'reservation_id' => $reservationId,
                'amount' => $deposit,
                'reference' => 'TEST-' . strtoupper(bin2hex(random_bytes(4))),
            ]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }

        return [
            'id' => $reservationId,
            'code' => $reservationCode,
            'room_number' => $room['room_number'],
            'category' => $room['category'],
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'nights' => $nights,
            'guests' => $guests,
            'total' => $total,
            'deposit' => $deposit,
            'email' => $user['email'],
        ];
    }

    public function dashboardStats(): array
    {
        return [
            'rooms' => (int) $this->pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn(),
            'available_rooms' => (int) $this->pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn(),
            'confirmed_reservations' => (int) $this->pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'confirmed'")->fetchColumn(),
            'clients' => (int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn(),
        ];
    }

    public function recent(int $limit = 6): array
    {
        $limit = max(1, min($limit, 20));

        return $this->pdo->query(
            "SELECT reservations.id, reservations.reservation_code, reservations.check_in,
                    reservations.check_out, reservations.total_amount, reservations.status,
                    users.full_name, rooms.room_number
             FROM reservations
             INNER JOIN users ON users.id = reservations.user_id
             INNER JOIN rooms ON rooms.id = reservations.room_id
             ORDER BY reservations.created_at DESC
             LIMIT {$limit}"
        )->fetchAll();
    }

    public function all(string $search = ''): array
    {
        $sql = "SELECT reservations.*, users.full_name, users.email, rooms.room_number,
                       room_types.name AS category
                FROM reservations
                INNER JOIN users ON users.id = reservations.user_id
                INNER JOIN rooms ON rooms.id = reservations.room_id
                INNER JOIN room_types ON room_types.id = rooms.room_type_id";
        $parameters = [];
        if ($search !== '') {
            $sql .= ' WHERE reservations.reservation_code LIKE :search
                      OR users.full_name LIKE :search
                      OR users.email LIKE :search
                      OR rooms.room_number LIKE :search';
            $parameters['search'] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY reservations.created_at DESC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function updateStatus(int $id, string $status): void
    {
        $allowed = ['pending', 'confirmed', 'cancelled', 'completed'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('El estado de reserva no es válido.');
        }
        $statement = $this->pdo->prepare('UPDATE reservations SET status = :status WHERE id = :id');
        $statement->execute(['status' => $status, 'id' => $id]);
    }

    private function activeUser(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, full_name, email FROM users WHERE id = :id AND is_active = 1'
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }
}
