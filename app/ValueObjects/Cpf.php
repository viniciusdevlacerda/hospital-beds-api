<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Exceptions\InvalidCpf;

final readonly class Cpf
{
    private const LENGTH = 11;

    private const CHECK_DIGIT_MODULUS = 11;

    private const FIRST_CHECK_DIGIT_POSITION = 9;

    private const SECOND_CHECK_DIGIT_POSITION = 10;

    private function __construct(public string $digits) {}

    public static function fromString(string $value): self
    {
        $digits = self::onlyDigits($value);

        if (! self::isValid($digits)) {
            throw new InvalidCpf($value);
        }

        return new self($digits);
    }

    public static function isValid(string $value): bool
    {
        $digits = self::onlyDigits($value);

        if (strlen($digits) !== self::LENGTH) {
            return false;
        }

        if (self::hasEveryDigitEqual($digits)) {
            return false;
        }

        return self::hasValidCheckDigitAt($digits, self::FIRST_CHECK_DIGIT_POSITION)
            && self::hasValidCheckDigitAt($digits, self::SECOND_CHECK_DIGIT_POSITION);
    }

    public function masked(): string
    {
        return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $this->digits)
            ?? $this->digits;
    }

    private static function hasEveryDigitEqual(string $digits): bool
    {
        return preg_match('/^(\d)\1+$/', $digits) === 1;
    }

    private static function hasValidCheckDigitAt(string $digits, int $position): bool
    {
        return self::checkDigitFor($digits, $position) === (int) $digits[$position];
    }

    private static function checkDigitFor(string $digits, int $position): int
    {
        $sum = 0;

        for ($index = 0; $index < $position; $index++) {
            $sum += (int) $digits[$index] * ($position + 1 - $index);
        }

        $remainder = $sum % self::CHECK_DIGIT_MODULUS;

        return $remainder < 2 ? 0 : self::CHECK_DIGIT_MODULUS - $remainder;
    }

    private static function onlyDigits(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }
}
