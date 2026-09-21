<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Concerns\RendersProblemResponse;
use App\ValueObjects\Cpf;
use RuntimeException;

final class PatientNotFound extends RuntimeException implements DomainException
{
    use RendersProblemResponse;

    public function __construct(private readonly Cpf $cpf)
    {
        parent::__construct("Nenhum paciente cadastrado com o CPF {$cpf->masked()}.");
    }

    public function errorCode(): string
    {
        return 'PATIENT_NOT_FOUND';
    }

    public function httpStatus(): int
    {
        return 404;
    }

    public function context(): array
    {
        return ['cpf' => $this->cpf->masked()];
    }
}
