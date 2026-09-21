<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Concerns\RendersProblemResponse;
use InvalidArgumentException;

final class InvalidCpf extends InvalidArgumentException implements DomainException
{
    use RendersProblemResponse;

    public function __construct(private readonly string $value)
    {
        parent::__construct("O CPF informado não é válido: {$value}.");
    }

    public function errorCode(): string
    {
        return 'INVALID_CPF';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    public function context(): array
    {
        return ['cpf' => $this->value];
    }
}
