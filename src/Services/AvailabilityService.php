<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RoomRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final class AvailabilityService
{
    public function __construct(private readonly RoomRepository $rooms)
    {
    }

    public function search(string $checkInText, string $checkOutText, int $guests): array
    {
        $checkIn = DateTimeImmutable::createFromFormat('!Y-m-d', $checkInText);
        $checkOut = DateTimeImmutable::createFromFormat('!Y-m-d', $checkOutText);
        if ($checkIn === false || $checkOut === false
            || $checkIn->format('Y-m-d') !== $checkInText
            || $checkOut->format('Y-m-d') !== $checkOutText) {
            throw new InvalidArgumentException('Las fechas deben utilizar el formato YYYY-MM-DD.');
        }
        if ($checkIn < new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('La fecha de llegada no puede estar en el pasado.');
        }

        $nights = (int) $checkIn->diff($checkOut)->format('%r%a');
        if ($nights < 1) {
            throw new InvalidArgumentException('La fecha de salida debe ser posterior a la fecha de llegada.');
        }
        if ($guests < 1 || $guests > 255) {
            throw new InvalidArgumentException('La cantidad de huéspedes no es válida.');
        }

        return [
            'rooms' => $this->rooms->availableForStay($checkInText, $checkOutText, $guests),
            'nights' => $nights,
        ];
    }
}
