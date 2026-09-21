<?php

declare(strict_types=1);

namespace App\Exceptions;

interface DomainException
{
    public function errorCode(): string;

    public function httpStatus(): int;

    /** @return array<string, mixed> */
    public function context(): array;
}
