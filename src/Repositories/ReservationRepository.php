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

    public function services(): array
    {
        return $this->pdo->query(
            "SELECT id, name, description, price FROM services
             WHERE is_active = 1 AND name = 'Traslado' ORDER BY id"
        )->fetchAll();
    }

    public function createConfirmed(array $data, int $userId): array
    {
        $room = $this->room((int) $data['room_id']);
        if ($room === null || $room['status'] !== 'available') {
            throw new RuntimeException('La habitación seleccionada no está disponible.');
        }

        $checkInText = (string) ($data['check_in'] ?? '');
        $checkOutText = (string) ($data['check_out'] ?? '');
        $checkIn = DateTimeImmutable::createFromFormat('!Y-m-d', $checkInText);
        $checkOut = DateTimeImmutable::createFromFormat('!Y-m-d', $checkOutText);
        if ($checkIn === false || $checkOut === false
            || $checkIn->format('Y-m-d') !== $checkInText
            || $checkOut->format('Y-m-d') !== $checkOutText) {
            throw new RuntimeException('Ingresa fechas válidas para la reserva.');
        }
        $nights = (int) $checkIn->diff($checkOut)->format('%r%a');
        $guests = (int) $data['guests'];

        if ($checkIn < new DateTimeImmutable('today')) {
            throw new RuntimeException('La fecha de llegada no puede estar en el pasado.');
        }
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
        $roomTotal = $dailyRate * $nights;
        $services = $this->selectedServices((array) ($data['services'] ?? []), $guests, $nights);
        $serviceTotal = array_sum(array_column($services, 'subtotal'));
        $total = $roomTotal + $serviceTotal;
        $deposit = round($total * 0.30, 2);
        $reservationCode = 'HPR-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $verificationToken = bin2hex(random_bytes(32));

        $this->pdo->beginTransaction();
        try {
            if (!$this->isAvailable((int) $room['id'], $checkIn->format('Y-m-d'), $checkOut->format('Y-m-d'))) {
                throw new RuntimeException('La habitación ya fue reservada para parte del período seleccionado.');
            }

            $reservation = $this->pdo->prepare(
                "INSERT INTO reservations
                    (reservation_code, verification_token, user_id, room_id, check_in, check_out, guests,
                     daily_rate, total_amount, deposit_amount, status)
                 VALUES
                    (:code, :verification_token, :user_id, :room_id, :check_in, :check_out, :guests,
                     :daily_rate, :total_amount, :deposit_amount, 'confirmed')"
            );
            $reservation->execute([
                'code' => $reservationCode,
                'verification_token' => $verificationToken,
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

            if ($services !== []) {
                $serviceInsert = $this->pdo->prepare(
                    'INSERT INTO reservation_services (reservation_id, service_id, quantity, unit_price)
                     VALUES (:reservation_id, :service_id, :quantity, :unit_price)'
                );
                foreach ($services as $service) {
                    $serviceInsert->execute([
                        'reservation_id' => $reservationId,
                        'service_id' => $service['id'],
                        'quantity' => $service['quantity'],
                        'unit_price' => $service['price'],
                    ]);
                }
            }

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
            'verification_token' => $verificationToken,
            'room_number' => $room['room_number'],
            'category' => $room['category'],
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'nights' => $nights,
            'guests' => $guests,
            'room_total' => $roomTotal,
            'service_total' => $serviceTotal,
            'total' => $total,
            'deposit' => $deposit,
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'services' => $services,
        ];
    }

    public function findByCode(string $code): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT reservations.*, users.full_name, users.email, rooms.room_number,
                    room_types.name AS category
             FROM reservations
             INNER JOIN users ON users.id = reservations.user_id
             INNER JOIN rooms ON rooms.id = reservations.room_id
             INNER JOIN room_types ON room_types.id = rooms.room_type_id
             WHERE reservations.reservation_code = :code
             LIMIT 1'
        );
        $statement->execute(['code' => trim($code)]);
        $reservation = $statement->fetch();
        if ($reservation === false) {
            return null;
        }
        $reservation['services'] = $this->servicesForReservation((int) $reservation['id']);

        return $reservation;
    }

    public function findByVerificationToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT reservations.*, users.full_name, users.email, rooms.room_number,
                    room_types.name AS category
             FROM reservations
             INNER JOIN users ON users.id = reservations.user_id
             INNER JOIN rooms ON rooms.id = reservations.room_id
             INNER JOIN room_types ON room_types.id = rooms.room_type_id
             WHERE reservations.verification_token = :token
             LIMIT 1'
        );
        $statement->execute(['token' => $token]);
        $reservation = $statement->fetch();
        if ($reservation === false) {
            return null;
        }
        $reservation['services'] = $this->servicesForReservation((int) $reservation['id']);

        return $reservation;
    }

    public function findForUserByCode(string $code, int $userId, bool $allowAdministrator = false): ?array
    {
        $reservation = $this->findByCode($code);
        if ($reservation === null) {
            return null;
        }
        if ((int) $reservation['user_id'] !== $userId && !$allowAdministrator) {
            return null;
        }

        return $reservation;
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

    private function selectedServices(array $ids, int $guests, int $nights): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->pdo->prepare(
            "SELECT id, name, description, price FROM services
             WHERE is_active = 1 AND name = 'Traslado' AND id IN ({$placeholders}) ORDER BY id"
        );
        $statement->execute($ids);
        $services = $statement->fetchAll();

        return array_map(static function (array $service) use ($guests, $nights): array {
            $quantity = 1;
            $service['quantity'] = $quantity;
            $service['price'] = (float) $service['price'];
            $service['subtotal'] = $service['price'] * $quantity;
            return $service;
        }, $services);
    }

    private function servicesForReservation(int $reservationId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT services.name, services.description, reservation_services.quantity,
                    reservation_services.unit_price,
                    reservation_services.quantity * reservation_services.unit_price AS subtotal
             FROM reservation_services
             INNER JOIN services ON services.id = reservation_services.service_id
             WHERE reservation_services.reservation_id = :reservation_id
             ORDER BY services.name'
        );
        $statement->execute(['reservation_id' => $reservationId]);

        return $statement->fetchAll();
    }
}
