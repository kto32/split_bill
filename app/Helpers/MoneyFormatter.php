<?php

declare(strict_types=1);

namespace App\Helpers;

final class MoneyFormatter
{
    public static function toCents(string $total): int
    {
        [$units, $cents] = array_pad(explode('.', $total, 2), 2, '0');

        $cents = str_pad($cents, 2, '0');

        return ((int) $units * 100) + (int) $cents;
    }

    public static function fromCents(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
