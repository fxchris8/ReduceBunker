<?php

namespace App\Helpers;

class NumberFormatter
{
    public static function idFormat($value): string
    {
        $float = (float) $value;

        $isInteger = floor($float) == $float;

        if ($isInteger) {
            return number_format($float, 0, ',', '.');
        }

        $decimals = self::countDecimals($float);
        return number_format($float, $decimals, ',', '.');
    }

    private static function countDecimals(float $value, int $maxPrecision = 4): int
    {
        for ($i = 1; $i <= $maxPrecision; $i++) {
            if (round($value, $i) == $value) {
                return $i;
            }
        }
        return $maxPrecision;
    }
}