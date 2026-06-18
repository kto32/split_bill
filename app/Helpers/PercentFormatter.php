<?php

declare(strict_types=1);

namespace App\Helpers;

final class PercentFormatter
{
    public static function toBasisPoints(string $percent): int
    {
        [$units, $fraction] = array_pad(explode('.', $percent, 2), 2, '0');

        $fraction = str_pad($fraction, 2, '0');

        return ((int) $units * 100) + (int) $fraction;
    }
}
