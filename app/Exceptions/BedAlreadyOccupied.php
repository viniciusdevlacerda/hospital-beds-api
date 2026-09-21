<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Concerns\RendersProblemResponse;
use App\Models\Bed;
use RuntimeException;

final class BedAlreadyOccupied extends RuntimeException implements DomainException
{
    use RendersProblemResponse;

    public function __construct(private readonly Bed $bed)
    {
        parent::__construct("O leito {$bed->code} já está ocupado.");
    }

    public function errorCode(): string
    {
        return 'BED_ALREADY_OCCUPIED';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    public function context(): array
    {
        return ['bed' => $this->bed->code];
    }
}
