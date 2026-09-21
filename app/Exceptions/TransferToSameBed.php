<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Concerns\RendersProblemResponse;
use App\Models\Bed;
use RuntimeException;

final class TransferToSameBed extends RuntimeException implements DomainException
{
    use RendersProblemResponse;

    public function __construct(private readonly Bed $bed)
    {
        parent::__construct("O paciente já ocupa o leito {$bed->code}; não há transferência a fazer.");
    }

    public function errorCode(): string
    {
        return 'TRANSFER_TO_SAME_BED';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    public function context(): array
    {
        return ['bed' => $this->bed->code];
    }
}
