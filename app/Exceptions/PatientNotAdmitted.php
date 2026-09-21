<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Concerns\RendersProblemResponse;
use App\ValueObjects\Cpf;
use RuntimeException;

final class PatientNotAdmitted extends RuntimeException implements DomainException
{
    use RendersProblemResponse;

    public function __construct(private readonly Cpf $cpf)
    {
        parent::__construct("O paciente de CPF {$cpf->masked()} não está internado em nenhum leito.");
    }

    public function errorCode(): string
    {
        return 'PATIENT_NOT_ADMITTED';
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
