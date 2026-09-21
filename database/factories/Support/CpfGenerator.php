<?php

declare(strict_types=1);

namespace Database\Factories\Support;

final class CpfGenerator
{
    public static function generate(): string
    {
        $digits = '';

        for ($position = 0; $position < 9; $position++) {
            $digits .= random_int(0, 9);
        }

        if (preg_match('/^(\d)\1{8}$/', $digits) === 1) {
            $digits = '123456789';
        }

        $digits .= self::checkDigit($digits, 9);

        return $digits.self::checkDigit($digits, 10);
    }

    private static function checkDigit(string $digits, int $length): int
    {
        $sum = 0;

        for ($position = 0; $position < $length; $position++) {
            $sum += (int) $digits[$position] * ($length + 1 - $position);
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
