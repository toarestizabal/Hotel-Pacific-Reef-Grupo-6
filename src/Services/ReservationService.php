<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReservationRepository;
use DateTimeImmutable;
use RuntimeException;

final class ReservationService
{
    private const DEPOSIT_RATE = 0.30;

    public function __construct(private readonly ReservationRepository $reservations)
    {
    }

    public function confirm(array $input, int $userId): array
    {
        $roomId = filter_var(
            $input['room_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($roomId === false) {
            throw new RuntimeException('La habitación seleccionada no es válida.');
        }

        $room = $this->reservations->room($roomId);
        if ($room === null || $room['status'] !== 'available') {
            throw new RuntimeException('La habitación seleccionada no está disponible.');
        }

        [$checkIn, $checkOut, $nights] = $this->validatedStay($input);
        $guests = filter_var(
            $input['guests'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => (int) $room['capacity']]]
        );
        if ($guests === false) {
            throw new RuntimeException('La cantidad de huéspedes no es válida para esta habitación.');
        }

        $user = $this->reservations->findActiveUser($userId);
        if ($user === null) {
            throw new RuntimeException('Debes iniciar sesión con una cuenta de cliente activa para reservar.');
        }

        $services = $this->reservations->findSelectedServices((array) ($input['services'] ?? []));
        $dailyRate = (float) $room['price'];
        $roomTotal = $dailyRate * $nights;
        $serviceTotal = array_sum(array_column($services, 'subtotal'));
        $total = $roomTotal + $serviceTotal;
        $deposit = round($total * self::DEPOSIT_RATE, 2);
        $reservationCode = $this->reservationCode();
        $verificationToken = bin2hex(random_bytes(32));

        $reservationId = $this->reservations->storeConfirmed([
            'code' => $reservationCode,
            'verification_token' => $verificationToken,
            'user_id' => $userId,
            'room_id' => (int) $room['id'],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => $guests,
            'daily_rate' => $dailyRate,
            'total_amount' => $total,
            'deposit_amount' => $deposit,
        ], $services, [
            'amount' => $deposit,
            'reference' => 'TEST-' . strtoupper(bin2hex(random_bytes(4))),
        ]);

        return [
            'id' => $reservationId,
            'code' => $reservationCode,
            'verification_token' => $verificationToken,
            'room_number' => $room['room_number'],
            'category' => $room['category'],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
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

    private function validatedStay(array $input): array
    {
        $checkInText = (string) ($input['check_in'] ?? '');
        $checkOutText = (string) ($input['check_out'] ?? '');
        $checkIn = DateTimeImmutable::createFromFormat('!Y-m-d', $checkInText);
        $checkOut = DateTimeImmutable::createFromFormat('!Y-m-d', $checkOutText);

        if ($checkIn === false || $checkOut === false
            || $checkIn->format('Y-m-d') !== $checkInText
            || $checkOut->format('Y-m-d') !== $checkOutText) {
            throw new RuntimeException('Ingresa fechas válidas para la reserva.');
        }

        $nights = (int) $checkIn->diff($checkOut)->format('%r%a');
        if ($checkIn < new DateTimeImmutable('today')) {
            throw new RuntimeException('La fecha de llegada no puede estar en el pasado.');
        }
        if ($nights < 1) {
            throw new RuntimeException('La fecha de salida debe ser posterior a la fecha de llegada.');
        }

        return [$checkInText, $checkOutText, $nights];
    }

    private function reservationCode(): string
    {
        return 'HPR-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
