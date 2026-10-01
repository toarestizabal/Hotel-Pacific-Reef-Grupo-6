<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use RuntimeException;

final class DateRange
{
    /** @return array{0: DateTimeImmutable, 1: DateTimeImmutable} */
    public static function validate(string $from, string $to, ?int $maximumDays = null): array
    {
        $start = self::date($from);
        $end = self::date($to);

        if ($start === null || $end === null || $end < $start) {
            throw new RuntimeException('Selecciona un rango de fechas válido.');
        }

        if ($maximumDays !== null && (int) $start->diff($end)->format('%a') > $maximumDays) {
            throw new RuntimeException("El período permite consultar hasta {$maximumDays} días por vez.");
        }

        return [$start, $end];
    }

    private static function date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }
}
