<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Concerns\RendersProblemResponse;
use App\Models\Bed;
use App\Models\Patient;
use RuntimeException;

final class PatientAlreadyAdmitted extends RuntimeException implements DomainException
{
    use RendersProblemResponse;

    public function __construct(
        private readonly Patient $patient,
        private readonly Bed $currentBed,
    ) {
        parent::__construct(
            "O paciente de CPF {$patient->cpf->masked()} já ocupa o leito {$currentBed->code}.",
        );
    }

    public function errorCode(): string
    {
        return 'PATIENT_ALREADY_ADMITTED';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    public function context(): array
    {
        return [
            'cpf' => $this->patient->cpf->masked(),
            'current_bed' => $this->currentBed->code,
        ];
    }
}
